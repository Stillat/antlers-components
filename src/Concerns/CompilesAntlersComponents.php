<?php

namespace Stillat\AntlersComponents\Concerns;

use Stillat\BladeParser\Nodes\Components\ComponentNode;

trait CompilesAntlersComponents
{
    protected function compileAntlersComponent(ComponentNode $componentNode): string
    {
        if ($componentNode->tagName == 'slot') {
            return $this->compileAntlersSlot($componentNode);
        }

        $tagName = 'isolated_partial';

        if ($componentNode->isClosingTag && ! $componentNode->isSelfClosing) {
            return "{{ /%{$tagName} }}";
        }

        $params = $this->compileParameters($componentNode);

        $suffix = '';

        if ($componentNode->isSelfClosing) {
            $suffix = '/';
        }

        return "{{ %{$tagName} src=\"{$componentNode->name}\" {$params} {$suffix}}}";
    }

    protected function compileAntlersSlot(ComponentNode $componentNode): string
    {
        $name = $this->getComponentName($componentNode);

        if ($componentNode->isClosingTag && ! $componentNode->isSelfClosing) {
            return "{{ /slot:{$name} }}";
        }

        $params = $this->compileParameters($componentNode);

        $suffix = '';

        if ($componentNode->isSelfClosing) {
            $suffix = '/';
        }

        return "{{ slot:{$name} {$params} {$suffix}}}";
    }
}
