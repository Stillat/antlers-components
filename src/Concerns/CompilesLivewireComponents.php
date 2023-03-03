<?php

namespace Stillat\AntlersComponents\Concerns;

use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;

trait CompilesLivewireComponents
{
    protected function compileLivewireComponent(ComponentNode $componentNode): string
    {
        $name = Str::after($componentNode->name, ':');
        $params = $this->compileParameters($componentNode);

        return "{{ %livewire:{$name} {$params}  /}}";
    }
}
