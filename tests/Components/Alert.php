<?php

namespace Stillat\AntlersComponents\Tests\Components;

use Illuminate\View\Component;

class Alert extends Component
{
    public function __construct(
        public string $type,
        public string $message,
    ) {
    }

    public function render()
    {
        return view('components.alert');
    }
}
