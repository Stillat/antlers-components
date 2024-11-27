<?php

namespace Stillat\AntlersComponents\Tests;

use Stillat\AntlersComponents\Utilities\StringUtilities;

class AntlersComponentsTest extends CompilerTestCase
{
    public function testItCanRenderBladeTemplates()
    {
        $template = <<<'EOT'
<a-hello title="{title}" />
EOT;

        $this->assertSame('I am the Blade:  A Title.', $this->renderString($template, ['title' => 'A Title']));
    }

    public function testItCanRenderAntlersTemplates()
    {
        $template = <<<'EOT'
<a-antlers_test :title="title" />
EOT;

        $this->assertSame('I am the Antlers: A Title.', $this->renderString($template, ['title' => 'A Title']));
    }

    public function testItCanRenderBladeTemplatesWithSlots()
    {
        $template = <<<'EOT'
<a:blade_slot>
    I am the slot content.
</a:blade_slot>
EOT;

        $expected = <<<'EOT'
Start

I am the slot content.

End
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function testItCanRenderBladeTemplateNamedSlotsWithoutBeingABladeComponent()
    {
        $template = <<<'EOT'
<a:blade_named_slots>
    <a:slot:title>I am the title!</a:slot:title>
    <a:slot:footer>I am the footer!</a:slot:footer>
    
    I am the regular slot content.
</a:blade_named_slots>
EOT;

        $expected = <<<'EOT'
<div class="border">
    <h1 class="text-lg">
        I am the title!
    </h1>

    I am the regular slot content.

    <footer class="text-gray-700">
        I am the footer!
    </footer>
</div>
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function testItCanRenderBladeTemplateNamedSlotsWithoutBeingABladeComponentMixedStyles()
    {
        $template = <<<'EOT'
<a:blade_named_slots class="some extra classes">
    <a:slot:title class="this thing">I am the title!</a:slot:title>
    <a-slot:footer class="another thing">I am the footer!</a-slot:footer>
    
    I am the regular slot content.
</a:blade_named_slots>
EOT;

        $expected = <<<'EOT'
<div class="border some extra classes">
    <h1 class="text-lg this thing">
        I am the title!
    </h1>

    I am the regular slot content.

    <footer class="text-gray-700 another thing">
        I am the footer!
    </footer>
</div>
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function testAttributesCanBeUsedInsideAntlers()
    {
        $template = <<<'EOT'
<a:antlers_template>
    <a-slot:title class="this thing">I am the title.</a-slot:title>
    <a-slot:footer class="this other thing">I am the title.</a-slot:footer>
    
    The slot content.
</a:antlers_template>
EOT;

        $expected = <<<'EOT'
<div class="border">
    <h1 class="border this thing">
        I am the title.
    </h1>
    
    The slot content.
    
    <footer class="text-gray-700 this other thing">
        I am the title.
    </footer>
</div>
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function testExplicitSlotsCanBeRendered()
    {
        $template = <<<'EOT'
<a:antlers_explicit_slots :$title>
    <a-slot:title class="this thing">I am the title. {{ title }}</a-slot:title>
    <a-slot:footer class="this other thing">I am the footer.</a-slot:footer>
    
    The slot content. {{ title }}
</a:antlers_explicit_slots>
EOT;

        $expected = <<<'EOT'
<div class="border">
    <h1 class="border this thing">
        I am the title. A Title!
    </h1>

    The slot content. A Title!

    <footer class="text-gray-700 this other thing">
        I am the footer.
    </footer>
</div>
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template, ['title' => 'A Title!']));
    }

    public function testAttributesCanBeUsedInsideAntlersWithMainAttributes()
    {
        $template = <<<'EOT'
<a:antlers_template class="some custom stuff here">
    <a-slot:title class="this thing">I am the title.</a-slot:title>
    <a-slot:footer class="this other thing">I am the title.</a-slot:footer>
    
    The slot content.
</a:antlers_template>
EOT;

        $expected = <<<'EOT'
<div class="border some custom stuff here">
    <h1 class="border this thing">
        I am the title.
    </h1>
    
    The slot content.
    
    <footer class="text-gray-700 this other thing">
        I am the title.
    </footer>
</div>
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function testAttributesCanBeUsedInsideAntlersWithMainAttributesIsolated()
    {
        $template = <<<'EOT'
<a:antlers_template class="some custom stuff here">
    <a-slot:title class="this thing">I am the title.</a-slot:title>
    <a-slot:footer class="this other thing">I am the title.</a-slot:footer>
    
    The slot content.
</a:antlers_template>
EOT;

        $expected = <<<'EOT'
<div class="border some custom stuff here">
    <h1 class="border this thing">
        I am the title.
    </h1>
    
    The slot content.
    
    <footer class="text-gray-700 this other thing">
        I am the title.
    </footer>
</div>
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function testParentNestedBehavior()
    {
        $template = <<<'EOT'
<a-parent :items="articles" :$title />
EOT;

        $data = [
            'title' => 'The Main Title',
            'articles' => [
                ['title' => 'Title One'],
                ['title' => 'Title Two'],
                ['title' => 'Title Three'],
            ],
        ];

        $expected = <<<'EOT'
<div>
    The Main Title

    Parent Nested: No
    <ul>
        <li>Nested: Yes Title: Title One Parent:: The Main Title</li><li>Nested: Yes Title: Title Two Parent:: The Main Title</li><li>Nested: Yes Title: Title Three Parent:: The Main Title</li>
    </ul>
</div>
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template, $data));
    }
}
