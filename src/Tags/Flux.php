<?php

namespace Stillat\AntlersComponents\Tags;

use Statamic\Tags\Tags;

class Flux extends Tags
{
    public function styles()
    {
        return app('flux')->styles();
    }

    public function appearance()
    {
        return app('flux')->fluxAppearance();
    }

    public function scripts()
    {
        app('livewire')->forceAssetInjection();

        return app('flux')->scripts();
    }
}
