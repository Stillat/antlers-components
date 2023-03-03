<?php

namespace Stillat\AntlersComponents\Utilities;

use Exception;
use Statamic\View\Antlers\Language\Runtime\GlobalRuntimeState;

class RuntimeIsolation
{
    /**
     * @throws Exception
     */
    public static function runInIsolation(callable $callable): mixed
    {
        $curIsolationLevel = GlobalRuntimeState::$requiresRuntimeIsolation;
        $curTrace = GlobalRuntimeState::$traceTagAssignments;
        $curStack = GlobalRuntimeState::$tracedRuntimeAssignments;
        // Tear down the traced state.
        GlobalRuntimeState::$traceTagAssignments = false;
        GlobalRuntimeState::$tracedRuntimeAssignments = [];
        GlobalRuntimeState::$requiresRuntimeIsolation = true;

        $result = null;

        try {
            $result = $callable();
        } catch (Exception $e) {
            throw $e;
        } finally {
            GlobalRuntimeState::$requiresRuntimeIsolation = $curIsolationLevel;
            GlobalRuntimeState::$traceTagAssignments = $curTrace;
            GlobalRuntimeState::$tracedRuntimeAssignments = $curStack;
        }

        return $result;
    }
}
