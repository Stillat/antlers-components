<?php

namespace Stillat\AntlersComponents\Tags;

use Illuminate\View\AnonymousComponent;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Illuminate\View\ComponentAttributeBag;
use ReflectionClass;
use Statamic\Tags\Tags;

class BladeHost extends Tags
{
    protected static $handle = 'blade_host';

    protected static $slots = [];

    private function makeComponentTagCompiler(): ComponentTagCompiler
    {
        /** @var BladeCompiler $bladeCompiler */
        $bladeCompiler = app(BladeCompiler::class);

        return new ComponentTagCompiler($bladeCompiler->getClassComponentAliases(), $bladeCompiler->getClassComponentNamespaces(), $bladeCompiler);
    }

    public function component(): string
    {
        $componentTagCompiler = $this->makeComponentTagCompiler();
        $componentName = $this->params->get('component');
        $className = $componentTagCompiler->componentClass($componentName);

        $attributes = new ComponentAttributeBag($this->params->except('component')->all());
        $constructorParameters = [];

        $scopeData = $this->context->all();
        $scopeData = array_merge($scopeData, $this->params->except('component')->all());

        $isAnonymous = false;
        $anonymousViewName = $className;

        if (! class_exists($className)) {
            $isAnonymous = true;
            $className = AnonymousComponent::class;
        }

        if ($constructor = (new ReflectionClass($className))->getConstructor()) {
            $constructorParameters = collect($constructor->getParameters())->map->getName()->all();
            $attributes = $attributes->except($constructorParameters);
            $constructorParameters = collect($scopeData)->only($constructorParameters)->all();
        }

        if ($isAnonymous) {
            $constructorParameters = array_merge($constructorParameters, ['view' => $anonymousViewName, 'data' => []]);
        }

        $__env = $this->context['__env'] ?? view();

        $component = $className::resolve($constructorParameters + ((array) $attributes->getIterator()));
        $component->withName($componentName);
        $__env->startComponent($component->resolveView(), $component->data());
        $component->withAttributes($attributes->getAttributes());

        echo $this->parse();

        return $__env->renderComponent();
    }

    public function componentSlot(): void
    {
        $__env = $this->context['__env'] ?? view();
        $slot = $this->params->get('slot');
        $context = $this->params->except('slot')->all();

        $__env->slot($slot, null, $context);
        $tempV = $this->parse();
        echo $tempV;
        $__env->endSlot();
        self::$slots[$slot] = $tempV;
    }
}
