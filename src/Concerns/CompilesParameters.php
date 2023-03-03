<?php

namespace Stillat\AntlersComponents\Concerns;

use Illuminate\Support\Str;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterType;

trait CompilesParameters
{
    protected function getParamValue(string $value): string
    {
        return Str::replace('"', '\\"', $value);
    }

    protected function compileParameters(ComponentNode $component): string
    {
        $compiledParameters = [];

        foreach ($component->parameters as $parameter) {
            if ($parameter->type == ParameterType::Parameter) {
                $compiledParameters[] = $parameter->name.'="'.$this->getParamValue($parameter->value).'"';
            } elseif ($parameter->type == ParameterType::Attribute) {
                $compiledParameters[] = $parameter->name.'="'.$parameter->name.'"';
            } elseif ($parameter->type == ParameterType::ShorthandDynamicVariable) {
                $compiledParameters[] = ':'.$parameter->materializedName.'="'.mb_substr($parameter->name, 2).'"';
            } elseif ($parameter->type == ParameterType::DynamicVariable) {
                $compiledParameters[] = ':'.$parameter->materializedName.'="'.$parameter->value.'"';
            }
        }

        return implode(' ', $compiledParameters);
    }
}
