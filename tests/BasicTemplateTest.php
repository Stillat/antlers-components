<?php

namespace Stillat\AntlersComponents\Tests;

use Stillat\AntlersComponents\Utilities\StringUtilities;

class BasicTemplateTest extends CompilerTestCase
{
    public function testItCompilesPartials()
    {
        $template = <<<'EOT'
<a-figure :block="block">
    <a-slot:title>The title</a-slot:title>
</a-figure>
EOT;

        $expected = <<<'EXPECTED'
{{ %isolated_partial src="figure" :block="block" }}
    {{ slot:title  }}The title{{ /slot:title }}
{{ /%isolated_partial }}
EXPECTED;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), trim($this->compiler->compile($template)));
    }

    public function testItCompilesIsolatedPartials()
    {
        $template = <<<'EOT'
<a-figure :block="block">
    <a-slot:title>The title</a-slot:title>
</a-figure>
EOT;

        $expected = <<<'EXPECTED'
{{ %isolated_partial src="figure" :block="block" }}
    {{ slot:title  }}The title{{ /slot:title }}
{{ /%isolated_partial }}
EXPECTED;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), trim($this->compiler->compile($template)));
    }

    public function testItCompilesAlternativeSyntax()
    {
        $template = <<<'EOT'
<a:figure :block="block">
    <a:slot:title>The title</a:slot:title>
</a:figure>
EOT;

        $expected = <<<'EXPECTED'
{{ %isolated_partial src="figure" :block="block" }}
    {{ slot:title  }}The title{{ /slot:title }}
{{ /%isolated_partial }}
EXPECTED;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), trim($this->compiler->compile($template)));
    }

    public function testItCompilesLivewireComponents()
    {
        $template = <<<'EOT'
<livewire:counter />
<livewire-counter />
EOT;

        $expected = <<<'EXPECTED'
{{ %livewire:counter   /}}
{{ %livewire:counter   /}}
EXPECTED;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), trim($this->compiler->compile($template)));
    }

    public function testItCompilesBladeComponents()
    {
        $template = <<<'EOT'
<x-alert :$title>
    <x-slot:title>The title</x-slot:title>
</x-alert>
EOT;

        $expected = <<<'EXPECTED'
{{ %blade_host:component component="alert" :title="title" }}
    {{ %blade_host:component_slot slot="title"  }}The title{{ /%blade_host:component_slot }}
{{ /%blade_host:component }}
EXPECTED;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), trim($this->compiler->compile($template)));
    }

    public function testItCompilesEverythingTogether()
    {
        $template = <<<'EOT'
<a-figure :block="block">
    <a-slot:title>The title</a-slot:title>
    <a-figure :block="block">
        <a-slot:title>The title</a-slot:title>
    </a-figure>
    <x-alert :$title>
        <x-slot:title>The title</x-slot:title>
    </x-alert>
</a-figure>
<x-alert :$title>
    <x-slot:title>The title</x-slot:title>
</x-alert>


<livewire:counter />
<livewire-counter />

<a-that something="else">
    <a-figure :block="block">
        <a-slot:title>The title</a-slot:title>
        <a-figure :block="block">
            <a-slot:title>The title</a-slot:title>
        </a-figure>
        <x-alert :$title>
            <x-slot:title>The title</x-slot:title>
        </x-alert>
    </a-figure>
    <x-alert :$title>
        <x-slot:title>The title</x-slot:title>
    </x-alert>
    
    
    <livewire:counter />
    <livewire-counter />
</a-that>
EOT;

        $expected = <<<'EXPECTED'
{{ %isolated_partial src="figure" :block="block" }}
    {{ slot:title  }}The title{{ /slot:title }}
    {{ %isolated_partial src="figure" :block="block" }}
        {{ slot:title  }}The title{{ /slot:title }}
    {{ /%isolated_partial }}
    {{ %blade_host:component component="alert" :title="title" }}
        {{ %blade_host:component_slot slot="title"  }}The title{{ /%blade_host:component_slot }}
    {{ /%blade_host:component }}
{{ /%isolated_partial }}
{{ %blade_host:component component="alert" :title="title" }}
    {{ %blade_host:component_slot slot="title"  }}The title{{ /%blade_host:component_slot }}
{{ /%blade_host:component }}


{{ %livewire:counter   /}}
{{ %livewire:counter   /}}

{{ %isolated_partial src="that" something="else" }}
    {{ %isolated_partial src="figure" :block="block" }}
        {{ slot:title  }}The title{{ /slot:title }}
        {{ %isolated_partial src="figure" :block="block" }}
            {{ slot:title  }}The title{{ /slot:title }}
        {{ /%isolated_partial }}
        {{ %blade_host:component component="alert" :title="title" }}
            {{ %blade_host:component_slot slot="title"  }}The title{{ /%blade_host:component_slot }}
        {{ /%blade_host:component }}
    {{ /%isolated_partial }}
    {{ %blade_host:component component="alert" :title="title" }}
        {{ %blade_host:component_slot slot="title"  }}The title{{ /%blade_host:component_slot }}
    {{ /%blade_host:component }}
    
    
    {{ %livewire:counter   /}}
    {{ %livewire:counter   /}}
{{ /%isolated_partial }}
EXPECTED;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), trim($this->compiler->compile($template)));
    }

    public function testItCompilesParameters()
    {
        $template = <<<'EOT'
<a-figure :$title :$aDifferentTitle :title="title" title="title" title />
EOT;

        $expected = <<<'EOT'
{{ %isolated_partial src="figure" :title="title" :a-different-title="aDifferentTitle" :title="title" title="title" title="title" /}}
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), trim($this->compiler->compile($template)));
    }

    public function testCompilingInterpolatedValues()
    {
        $template = <<<'EOT'
<x-button @click="{{ handle }}++">
  Submit
</x-button>
EOT;

        $expected = <<<'EXP'
{{ %blade_host:component component="button" @click="{{ handle }}++" }}
  Submit
{{ /%blade_host:component }}
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), StringUtilities::normalizeLineEndings(trim($this->compiler->compile($template))));

        $template = <<<'EOT'
<x-button @click="{handle}++">
  Submit
</x-button>
EOT;

        $expected = <<<'EXP'
{{ %blade_host:component component="button" @click="{handle}++" }}
  Submit
{{ /%blade_host:component }}
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), StringUtilities::normalizeLineEndings(trim($this->compiler->compile($template))));
    }

    public function testSimpleUnknownEchoParametersAreCompiled()
    {
        $template = <<<'EOT'
<x-button {{ js_attributes }}>
  Submit
</x-button>
EOT;

        $expected = <<<'EXP'
{{ %blade_host:component component="button" :js_attributes="js_attributes" }}
  Submit
{{ /%blade_host:component }}
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), StringUtilities::normalizeLineEndings(trim($this->compiler->compile($template))));
    }

    public function testComplexUnknownEchoParametersAreCompiled()
    {
        $template = <<<'EOT'
<x-button {{ js_attributes | upper }}>
  Submit
</x-button>
EOT;

        $expected = <<<'EXP'
{{ %blade_host:component component="button" js_attributes="{js_attributes | upper}" }}
  Submit
{{ /%blade_host:component }}
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), StringUtilities::normalizeLineEndings(trim($this->compiler->compile($template))));
    }
}
