<?php

namespace Stillat\AntlersComponents;

use Illuminate\Support\Str;
use Stillat\AntlersComponents\Concerns\CompilesAntlersComponents;
use Stillat\AntlersComponents\Concerns\CompilesBladeComponents;
use Stillat\AntlersComponents\Concerns\CompilesLivewireComponents;
use Stillat\AntlersComponents\Concerns\CompilesParameters;
use Stillat\AntlersComponents\Utilities\StringUtilities;
use Stillat\BladeParser\Document\Document;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Parser\DocumentParser;

class AntlersCompiler
{
    use CompilesBladeComponents, CompilesParameters, CompilesLivewireComponents, CompilesAntlersComponents;

    protected static array $compileCache = [];

    public function compile(string $content): string
    {
        if (! Str::contains($content, ['<a-', '<a:', '<x-', '<x:', '<livewire:', '<livewire-'])) {
            return $content;
        }

        $slug = '_comp'.md5($content);

        if (array_key_exists($slug, self::$compileCache)) {
            return self::$compileCache[$slug];
        }

        $content = StringUtilities::normalizeLineEndings($content);

        $parser = new DocumentParser();
        $parser->registerCustomComponentTags(['a', 'livewire'])
            ->onlyParseComponents()
            ->parse($content);

        $doc = new Document();
        $doc->syncFromParser($parser);

        $compiled = "\n";

        foreach ($doc->getNodes() as $node) {
            if ($node instanceof ComponentNode) {
                if (Str::startsWith($node->content, ['<a-', '</a-', '<a:', '</a:'])) {
                    $compiled .= $this->compileAntlersComponent($node);
                } elseif (Str::startsWith($node->content, ['<livewire-', '<livewire:'])) {
                    $compiled .= $this->compileLivewireComponent($node);
                } else {
                    $compiled .= $this->compileBlade($node);
                }
            } else {
                $compiled .= $node->unescapedContent;
            }
        }

        self::$compileCache[$slug] = $compiled;

        return $compiled;
    }

    protected function getComponentName(ComponentNode $node): string
    {
        $name = $node->name;

        if ($node->tagName == 'slot') {
            $name = Str::after($name, ':');
        }

        return $name;
    }
}
