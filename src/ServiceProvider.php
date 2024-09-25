<?php

namespace Stillat\AntlersComponents;

use Statamic\Providers\AddonServiceProvider;
use Statamic\View\Antlers\Language\Runtime\RuntimeParser;
use Stillat\AntlersComponents\Tags\BladeHost;
use Stillat\AntlersComponents\Tags\Flux;
use Stillat\AntlersComponents\Tags\IsolatedPartial;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        IsolatedPartial::class,
        BladeHost::class,
        Flux::class,
    ];

    public function getTags(): array
    {
        return $this->tags;
    }

    public function bootAddon()
    {
        $this->app->resolving(RuntimeParser::class, function (RuntimeParser $parser) {
            $parser->preparse(function ($content) {
                return (new AntlersCompiler())->compile($content);
            });
        });
    }
}
