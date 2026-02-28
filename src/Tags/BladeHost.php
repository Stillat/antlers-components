<?php

/**
 * PATCHED FILE - Do not edit vendor directly
 *
 * This is a patched version of stillat/antlers-components BladeHost.php
 * Fixes: https://github.com/Stillat/antlers-components/issues/10
 *
 * Changes:
 * - Added nesting depth tracking for nested Blade components
 * - Added normalizeParams() to convert string booleans ("false" -> false)
 * - Pre-populate component data so @aware works with parent props (variant, indicator, etc.)
 *
 * Remove this patch when the upstream PR is merged and package is updated.
 */

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

    /**
     * Track nesting depth for pre-populating component data.
     * This is separate from the actual component stack which isn't
     * incremented until startComponent is called.
     */
    protected static int $nestingDepth = 0;

    /**
     * Convert string boolean values to actual booleans.
     * This handles cases like indicator="false" which passes the string "false".
     *
     * Note: :indicator="false" does NOT work because Antlers interprets "false"
     * as a variable name lookup, not a literal. Use indicator="false" (without colon)
     * or an Antlers variable: {{ show_indicator = false }} :indicator="show_indicator"
     */
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

        // Pre-populate component data so nested components can access parent props via @aware
        // This mimics what startComponent does, but without starting the output buffer yet
        $componentData = $component->data();

        // Use reflection to access protected properties
        $factoryReflection = new ReflectionClass($__env);

        // Get current stack size plus our virtual nesting depth to determine index
        // This ensures nested BladeHost components don't overwrite parent data
        $stackIndex = 0;
        if ($factoryReflection->hasProperty('componentStack')) {
            $stackProp = $factoryReflection->getProperty('componentStack');
            $stackProp->setAccessible(true);
            $stackIndex = count($stackProp->getValue($__env));
        }
        $stackIndex += self::$nestingDepth;
        self::$nestingDepth++;

        // Pre-populate componentData so @aware can find parent props
        $existingData = [];
        if ($factoryReflection->hasProperty('componentData')) {
            $componentDataProp = $factoryReflection->getProperty('componentData');
            $componentDataProp->setAccessible(true);
            $existingData = $componentDataProp->getValue($__env);
            $existingData[$stackIndex] = $componentData;
            $componentDataProp->setValue($__env, $existingData);
        }

        // Set currentComponentData for @aware lookups during slot parsing
        $previousData = [];
        if ($factoryReflection->hasProperty('currentComponentData')) {
            $currentDataProp = $factoryReflection->getProperty('currentComponentData');
            $currentDataProp->setAccessible(true);
            $previousData = $currentDataProp->getValue($__env);
            $currentDataProp->setValue($__env, array_merge($previousData, $componentData));
        }

        // Capture slot content - nested components can now access parent data via @aware
        $slotContent = $this->parse();

        $__env->startComponent($component->resolveView(), $componentData);
        $component->withAttributes($attributes->getAttributes());

        echo $slotContent;

        $result = $__env->renderComponent();

        // Restore previous currentComponentData after rendering
        if (isset($currentDataProp)) {
            $currentDataProp->setValue($__env, $previousData);
        }

        // Clean up the pre-populated componentData after rendering
        if (isset($componentDataProp)) {
            unset($existingData[$stackIndex]);
            $componentDataProp->setValue($__env, $existingData);
        }

        // Decrement nesting depth
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
