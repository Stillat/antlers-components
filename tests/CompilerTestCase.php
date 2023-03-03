<?php

namespace Stillat\AntlersComponents\Tests;

use Orchestra\Testbench\TestCase;
use Statamic\Tags\Loader;
use Statamic\View\Antlers\Language\Analyzers\NodeTypeAnalyzer;
use Statamic\View\Antlers\Language\Lexer\AntlersLexer;
use Statamic\View\Antlers\Language\Parser\DocumentParser;
use Statamic\View\Antlers\Language\Parser\LanguageParser;
use Statamic\View\Antlers\Language\Runtime\EnvironmentDetails;
use Statamic\View\Antlers\Language\Runtime\GlobalRuntimeState;
use Statamic\View\Antlers\Language\Runtime\ModifierManager;
use Statamic\View\Antlers\Language\Runtime\NodeProcessor;
use Statamic\View\Antlers\Language\Runtime\RuntimeParser;
use Statamic\View\Antlers\Language\Utilities\StringUtilities;
use Statamic\View\Cascade;
use Stillat\AntlersComponents\AntlersCompiler;
use Stillat\AntlersComponents\ServiceProvider;

class CompilerTestCase extends TestCase
{
    protected AntlersCompiler $compiler;

    public function setup(): void
    {
        parent::setUp();
        $this->compiler = new AntlersCompiler();

        /** @var ServiceProvider $provider */
        $provider = collect(app()->getProviders(ServiceProvider::class))->first();

        // Quick workaround to get all of our tags available.
        foreach ($provider->getTags() as $tag) {
            $tag::register();
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            \Statamic\Providers\StatamicServiceProvider::class,
            \Stillat\AntlersComponents\ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return ['Statamic' => 'Statamic\Statamic'];
    }

    protected function getEnvironmentSetUp($app)
    {
        // We changed the default sites setup but the tests assume defaults like the following.
        $app['config']->set('statamic.sites', [
            'default' => 'en',
            'sites' => [
                'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://localhost/'],
            ],
        ]);
        $app['config']->set('auth.providers.users.driver', 'statamic');
        $app['config']->set('statamic.stache.watcher', true);
        $app['config']->set('statamic.users.repository', 'file');
        $app['config']->set('statamic.stache.stores.users', [
            'class' => \Statamic\Stache\Stores\UsersStore::class,
            'directory' => __DIR__.'/__fixtures__/users',
        ]);

        $app['config']->set('statamic.stache.stores.taxonomies.directory', __DIR__.'/__fixtures__/content/taxonomies');
        $app['config']->set('statamic.stache.stores.terms.directory', __DIR__.'/__fixtures__/content/taxonomies');
        $app['config']->set('statamic.stache.stores.collections.directory', __DIR__.'/__fixtures__/content/collections');
        $app['config']->set('statamic.stache.stores.entries.directory', __DIR__.'/__fixtures__/content/collections');
        $app['config']->set('statamic.stache.stores.navigation.directory', __DIR__.'/__fixtures__/content/navigation');
        $app['config']->set('statamic.stache.stores.globals.directory', __DIR__.'/__fixtures__/content/globals');
        $app['config']->set('statamic.stache.stores.asset-containers.directory', __DIR__.'/__fixtures__/content/assets');
        $app['config']->set('statamic.stache.stores.nav-trees.directory', __DIR__.'/__fixtures__/content/structures/navigation');
        $app['config']->set('statamic.stache.stores.collection-trees.directory', __DIR__.'/__fixtures__/content/structures/collections');

        $app['config']->set('statamic.api.enabled', true);
        $app['config']->set('statamic.graphql.enabled', true);
        $app['config']->set('statamic.editions.pro', true);

        $app['config']->set('cache.stores.array.driver', 'null');
        $app['config']->set('cache.stores.outpost', [
            'driver' => 'file',
            'path' => storage_path('framework/cache/outpost-data'),
        ]);

        $viewPaths = $app['config']->get('view.paths');
        $viewPaths[] = __DIR__.'/__fixtures__/views/';

        $app['config']->set('view.paths', $viewPaths);
    }

    protected function renderString($text, $data = [], $withCoreTagsAndModifiers = true)
    {
        $text = $this->compiler->compile($text);

        ModifierManager::$statamicModifiers = null;
        GlobalRuntimeState::resetGlobalState();

        $documentParser = new DocumentParser();
        $loader = new Loader();
        $envDetails = new EnvironmentDetails();

        if ($withCoreTagsAndModifiers) {
            $envDetails->setTagNames(app()->make('statamic.tags')->keys()->all());
            $envDetails->setModifierNames(app()->make('statamic.modifiers')->keys()->all());

            NodeTypeAnalyzer::$environmentDetails = $envDetails;
        }

        $processor = new NodeProcessor($loader, $envDetails);
        $processor->setData($data);

        $runtimeParser = new RuntimeParser($documentParser, $processor, new AntlersLexer(), new LanguageParser());
        $processor->setAntlersParserInstance($runtimeParser);

        if ($withCoreTagsAndModifiers) {
            $runtimeParser->cascade(app(Cascade::class));
        }

        return trim(StringUtilities::normalizeLineEndings((string) $runtimeParser->parse($text, $data)));
    }
}
