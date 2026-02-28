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

    protected static int $nestingDepth = 0;

    private function normalizeParams(array $params): array
    {
        return collect($params)->map(function ($value) {
            if ($value === 'true') {
                return true;
            }
            if ($value === 'false') {
                return false;
            }
            if ($value === 'null') {
                return null;
            }

            return $value;
        })->all();
    }

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

        $normalizedParams = $this->normalizeParams($this->params->except('component')->all());
        $attributes = new ComponentAttributeBag($normalizedParams);
        $constructorParameters = [];

        $scopeData = $this->context->all();
        $scopeData = array_merge($scopeData, $normalizedParams);

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
            $constructorParameters = array_merge($constructorParameters, [
                'view' => $anonymousViewName,
                'data' => $normalizedParams,
            ]);
        }

        $__env = $this->context['__env'] ?? view();

        $component = $className::resolve($constructorParameters + ((array) $attributes->getIterator()));
        $component->withName($componentName);

        $componentData = $component->data();
        $factoryReflection = new ReflectionClass($__env);

        // Pre-populate componentData/currentComponentData via reflection so
        // nested components can resolve parent props through @aware.
        $stackIndex = 0;
        if ($factoryReflection->hasProperty('componentStack')) {
            $stackProp = $factoryReflection->getProperty('componentStack');
            $stackProp->setAccessible(true);
            $stackIndex = count($stackProp->getValue($__env));
        }
        $stackIndex += self::$nestingDepth;
        self::$nestingDepth++;

        $existingData = [];
        if ($factoryReflection->hasProperty('componentData')) {
            $componentDataProp = $factoryReflection->getProperty('componentData');
            $componentDataProp->setAccessible(true);
            $existingData = $componentDataProp->getValue($__env);
            $existingData[$stackIndex] = $componentData;
            $componentDataProp->setValue($__env, $existingData);
        }

        $previousData = [];
        if ($factoryReflection->hasProperty('currentComponentData')) {
            $currentDataProp = $factoryReflection->getProperty('currentComponentData');
            $currentDataProp->setAccessible(true);
            $previousData = $currentDataProp->getValue($__env);
            $currentDataProp->setValue($__env, array_merge($previousData, $componentData));
        }

        // startComponent must be called before parse() so that named slots
        // registered by componentSlot() attach to the correct component.
        $__env->startComponent($component->resolveView(), $componentData);
        $component->withAttributes($attributes->getAttributes());

        echo $this->parse();

        $result = $__env->renderComponent();

        if (isset($currentDataProp)) {
            $currentDataProp->setValue($__env, $previousData);
        }

        if (isset($componentDataProp)) {
            unset($existingData[$stackIndex]);
            $componentDataProp->setValue($__env, $existingData);
        }

        self::$nestingDepth--;

        return $result;
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
