<?php

namespace Stillat\AntlersComponents\Concerns;

use Stillat\BladeParser\Nodes\Components\ComponentNode;

trait CompilesBladeComponents
{
    protected function compileBlade(ComponentNode $componentNode, string $prefix = ''): string
    {
        $params = $this->compileParameters($componentNode);
        $name = $prefix.$this->getComponentName($componentNode);

        $antlersTag = 'component';
        $paramName = 'component';

        if ($componentNode->tagName == 'slot') {
            $antlersTag = 'component_slot';
            $paramName = 'slot';
        }

        if ($antlersTag == 'component_slot') {
            $rewrite = false;
            /*if (array_key_exists($componentNode->parent->id, $this->tagRetargeting)) {
                $rewrite = true;
            } elseif ($componentNode->isClosingTag && $componentNode->parent != null && array_key_exists($componentNode->parent->parent->id, $this->tagRetargeting)) {
                $rewrite = true;
            }*/

            if ($rewrite) {
                // We need to emit an Antlers slot.
                if ($componentNode->isClosingTag && ! $componentNode->isSelfClosing) {
                    return "{{ /slot:{$name} }}";
                } elseif ($componentNode->isSelfClosing) {
                    return "{{ slot:{$name} {$params} /}}";
                }

                return "{{ %slot:{$name} {$params} }}";
            }
        }

        $suffix = ' ';

        if ($componentNode->isClosingTag && ! $componentNode->isSelfClosing) {
            return "{{ /%blade_host:{$antlersTag} }}";
        }

        if ($componentNode->isSelfClosing) {
            $suffix = ' /';
        }

        return "{{ %blade_host:{$antlersTag} {$paramName}=\"{$name}\" {$params}{$suffix}}}";
    }
}
