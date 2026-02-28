<?php

namespace Stillat\AntlersComponents\Tests;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use Stillat\AntlersComponents\Tests\Components\Alert;
use Stillat\AntlersComponents\Tests\Components\Card;
use Stillat\AntlersComponents\Utilities\StringUtilities;

class BladeComponentsTest extends CompilerTestCase
{
    protected BladeCompiler $blade;

    protected function setup(): void
    {
        parent::setup();
        Blade::component(Alert::class);
        Blade::component(Card::class);
    }

    public function test_basic_blade_components()
    {
        $template = <<<'EOT'
{{ message = 'The message.'; }}
<x-alert type="error" :message="$message" />
EOT;

        $expected = <<<'EOT'
<div class="alert alert-error">
    The message.
</div>
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function test_blade_components_with_slots()
    {
        $template = <<<'EOT'
<x-card class="shadow-sm">
    <x-slot:heading class="font-bold">
        Heading
    </x-slot>
 
    Content
 
    <x-slot:footer class="text-sm">
        Footer
    </x-slot>
</x-card>
EOT;

        $expected = <<<'EXP'
<div class="border shadow-sm">
    <h1 class="text-lg font-bold">
        Heading
    </h1>

    Content

    <footer class="text-gray-700 text-sm">
        Footer
    </footer>
</div>
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function test_aware_directive_with_nested_components()
    {
        $template = <<<'EOT'
<x-radio.group variant="cards" indicator="false">
    <x-radio label="Option 1"/>
</x-radio.group>

EOT;

        $expected = <<<'EXP'
<div data-variant="cards" data-indicator="false">
    <label data-variant="cards" data-indicator="false">Option 1</label>
</div>
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function test_rendering_anonymous_components()
    {
        $template = <<<'EOT'
<x-button />
<x-input />
<x-input type="password" />
EOT;

        $expected = <<<'EXP'
<div>
    A Blade Button!
</div>
<input type="text" />
<input type="password" />
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }
}
