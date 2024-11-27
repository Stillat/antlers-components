<?php

namespace Stillat\AntlersComponents\Tags;

use Exception;
use Facades\Statamic\View\Cascade;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Statamic\Facades\Antlers;
use Statamic\Facades\Parse;
use Statamic\Fields\ArrayableString;
use Statamic\Tags\Partial;
use Statamic\View\Antlers\Language\Nodes\AntlersNode;
use Statamic\View\Antlers\Language\Parser\DocumentParser;
use Statamic\View\Antlers\Language\Runtime\NodeProcessor;
use Stillat\AntlersComponents\AntlersCompiler;
use Stillat\AntlersComponents\Utilities\RuntimeIsolation;

class IsolatedPartial extends Partial
{
    public static $handle = 'isolated_partial';

    protected static array $pathCache = [];

    protected static array $slotCache = [];

    protected static array $isolatedStack = [];

    protected AntlersCompiler $compiler;

    public function __construct()
    {
        $this->compiler = new AntlersCompiler();
    }

    /**
     * @throws Exception
     */
    public function render($partial): string
    {
        if (count(self::$isolatedStack) == 0) {
            return RuntimeIsolation::runInIsolation(fn () => $this->renderPartial());
        }

        return $this->renderPartial();
    }

    private function extractContentAndFrontMatter(string $fileName): array
    {
        if (array_key_exists($fileName, self::$pathCache)) {
            return self::$pathCache[$fileName];
        }

        self::$pathCache[$fileName] = $this->extractFrontMatter(file_get_contents($fileName));

        return self::$pathCache[$fileName];
    }

    private function getSlots(string $content): array
    {
        $cacheKey = 'slots_'.md5($content);

        if (array_key_exists($cacheKey, self::$slotCache)) {
            return self::$slotCache[$cacheKey];
        }

        /** @var DocumentParser $parser */
        $parser = app(DocumentParser::class);
        $nrRes = $parser->parse($this->content);

        $slots = [];

        foreach ($nrRes as $r) {
            if ($r instanceof AntlersNode && $r->name != null && $r->name->name == 'slot') {
                $slots[$r->name->methodPart] = [$r->runtimeContent, $r];
            }
        }

        self::$slotCache[$cacheKey] = $slots;

        return $slots;
    }

    private function getIsolationContext()
    {
        $context = [];

        foreach (self::$isolatedStack as $stack) {
            $context = array_merge($context, $stack);
        }

        return collect($context);
    }

    private function renderPartial(): string
    {
        $src = $this->viewName($this->params->get('src'));
        $view = view($src);

        [$frontMatter, $contents] = $this->extractContentAndFrontMatter($view->getPath());

        $incomingContext = $this->getIsolationContext()->except(['attributes', '__parent', '__is_nested', '__depth'])->all();
        $data = array_merge(Cascade::instance()->toArray(), $incomingContext, $this->params->all());

        unset($data['views']);
        $frontMatter = array_merge($frontMatter, $data);
        $data['view'] = $frontMatter;
        unset($data['view']['view']);

        if (count(self::$isolatedStack) >= 1) {
            $data['__parent'] = self::$isolatedStack[count(self::$isolatedStack) - 1];
            $data['__is_nested'] = true;
            $data['__depth'] = count(self::$isolatedStack);
        } else {
            $data['__parent'] = null;
            $data['__is_nested'] = false;
            $data['__depth'] = 0;
        }

        $otherParams = $this->params->except(['src']);
        $data['attributes'] = new ComponentAttributeBag($otherParams->all());

        self::$isolatedStack[] = $data;

        $namedSlots = $this->getSlots($this->content);
        $defaultSlot = trim(Antlers::parse($this->content, $data));
        $proc = app(NodeProcessor::class);

        if (Str::endsWith($view->getPath(), '.blade.php')) {
            foreach ($namedSlots as $slotName => $slotTemplate) {
                $attributes = [];

                if (count($slotTemplate[1]->parameters) > 0) {
                    $attributes = $slotTemplate[1]->getParameterValues($proc, $data);
                }

                $evaluated = trim(Antlers::parse($slotTemplate[0], $data));
                $data[$slotName] = new ComponentSlot($evaluated, $attributes);
            }

            $data['slot'] = new ComponentSlot($defaultSlot, []);
        } else {
            $extraSlots = [];

            foreach ($namedSlots as $slotName => $slotTemplate) {
                $attributes = [];

                if (count($slotTemplate[1]->parameters) > 0) {
                    $attributes = $slotTemplate[1]->getParameterValues($proc, $data);
                }

                $evaluated = trim(Antlers::parse($slotTemplate[0], $data));
                $slotValue = new ArrayableString($evaluated, ['attributes' => new ComponentAttributeBag($attributes)]);

                $extraSlots[$slotName] = $slotValue;
                $data[$slotName] = $slotValue;
            }

            $data['slot'] = new ArrayableString($defaultSlot, array_merge(
                ['attributes' => new ComponentAttributeBag($this->params->except('src')->all())],
                $extraSlots
            ));
        }

        $otherParams = $this->params->except('src');
        $data['attributes'] = new ComponentAttributeBag($otherParams->all());

        if (Str::endsWith($view->getPath(), '.blade.php')) {
            $result = view($src)->with($data)->render();
        } else {
            // Wrap the partial contents in a view tag pair, resolving
            // any context data against the frontmatter, effectively
            // resolving any parameter default values automatically.
            $result = Antlers::parse('{{ view }}'.$contents.'{{ /view }}', $data);
        }

        array_pop(self::$isolatedStack);

        return $result;
    }

    private function extractFrontMatter($contents): array
    {
        $parsed = Parse::frontMatter($contents);

        return [$parsed['data'], $this->compiler->compile($parsed['content'] ?? '')];
    }
}
