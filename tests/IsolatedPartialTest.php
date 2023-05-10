<?php

namespace Stillat\AntlersComponents\Tests;

use Stillat\AntlersComponents\Utilities\StringUtilities;

class IsolatedPartialTest extends CompilerTestCase
{
    protected array $data = [
        'articles' => [
            ['title' => 'Title One'],
            ['title' => 'Title Two'],
            ['title' => 'Title Three'],
        ],
    ];

    public function testIsolatedDoesNotInheritData()
    {
        $template = <<<'EOT'
{{ articles }}
Outside title: {{ title }}
Isolated 1: <a-test_partial>{{ title }}</a-test_partial>
Not Isolated: <a-test_partial :$title>{{ title }}</a-test_partial>
Isolated 2: <a-test_partial>{{ title }}</a-test_partial>
Param Isolated: <a-test_partial :$title>{{ title }}</a-test_partial>
{{ /articles }}
EOT;

        $expected = <<<'EOT'
Outside title: Title One
Isolated 1: Test slot value: 
Not Isolated: Test slot value: Title One
Isolated 2: Test slot value: 
Param Isolated: Test slot value: Title One

Outside title: Title Two
Isolated 1: Test slot value: 
Not Isolated: Test slot value: Title Two
Isolated 2: Test slot value: 
Param Isolated: Test slot value: Title Two

Outside title: Title Three
Isolated 1: Test slot value: 
Not Isolated: Test slot value: Title Three
Isolated 2: Test slot value: 
Param Isolated: Test slot value: Title Three
EOT;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template, $this->data));
    }

    public function testIsolatedPartialsTossDataAway()
    {
        $template = <<<'EOT'
{{ title = 'Hello!'; }}
<a-test_partial>
    {{ title = 'My new title'; }}
    {{ title }}
</a-test_partial>
{{ title }}
EOT;

        $expected = <<<'EXP'
Test slot value: My new title
Hello!
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function testParentIsolatedPartialPassesDataToNestedPartials()
    {
        $template = <<<'EOT'
{{ title = 'Hello!'; }}
<a-test_partial>
    {{ title = 'My new title'; }}
    
    <a-test_partial>
        {{ title = 'A new title'; }}
        Inner2: {{ title }}
        
        {{ title = 'Just another title'; }}
    </a-test_partial>
    
    Inner1: {{ title }}
</a-test_partial>
{{ title }}
EOT;

        $expected = <<<'EXP'
Test slot value: Test slot value: Inner2: A new title
    
    Inner1: Just another title
Hello!
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template));
    }

    public function testBladePartialsAreIsolatedFromParentData()
    {
        $template = <<<'EOT'
{{ articles }}
Outside title: {{ title }}
Isolated 1: <a-hello />
Not Isolated: <a-hello :$title />
Isolated 2: <a-hello />
Param Isolated: <a-hello :$title>{{ title }}</a-hello>
{{ /articles }}
EOT;

        $expected = <<<'EXP'
Outside title: Title One
Isolated 1: I am the Blade: .
Not Isolated: I am the Blade:  Title One.
Isolated 2: I am the Blade: .
Param Isolated: I am the Blade:  Title One.

Outside title: Title Two
Isolated 1: I am the Blade: .
Not Isolated: I am the Blade:  Title Two.
Isolated 2: I am the Blade: .
Param Isolated: I am the Blade:  Title Two.

Outside title: Title Three
Isolated 1: I am the Blade: .
Not Isolated: I am the Blade:  Title Three.
Isolated 2: I am the Blade: .
Param Isolated: I am the Blade:  Title Three.
EXP;

        $this->assertSame(StringUtilities::normalizeLineEndings($expected), $this->renderString($template, $this->data));
    }
}
