<?php

namespace Stillat\AntlersComponents\Tests\Components;

use Illuminate\View\Component;

class Card extends Component
{
    public function render()
    {
        return view('components.card');
    }
}
