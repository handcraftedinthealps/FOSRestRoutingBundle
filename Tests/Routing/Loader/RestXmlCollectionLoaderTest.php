<?php

/*
 * This file is part of Handcrafted in the Alps - Rest Routing Bundle Project.
 *
 * (c) 2011-2020 FriendsOfSymfony <http://friendsofsymfony.github.com/>
 * (c) 2020 Sulu GmbH <hello@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HandcraftedInTheAlps\RestRoutingBundle\Tests\Routing\Loader;

use HandcraftedInTheAlps\RestRoutingBundle\Routing\Loader\RestRouteProcessor;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\Loader\RestXmlCollectionLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Routing\RouteCollection;

/**
 * RestXmlCollectionLoader test.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class RestXmlCollectionLoaderTest extends LoaderTest
{
    /**
     * Test that route route not found.
     */
    public function testLoadThrowsExceptionWithInvalidRouteParent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find parent resource with name');

        $this->loadFromXmlCollectionFixture('invalid_route_parent.xml');
    }

    /**
     * Test that invalid tag.
     */
    public function testLoadThrowsExceptionWithInvalidTag()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/This element is not expected. Expected is one of/');

        $this->loadFromXmlCollectionFixture('invalid_tag.xml');
    }

    /**
     * Test that XML collection gets parsed correctly.
     */
    public function testUsersFixture()
    {
        $collection = $this->loadFromXmlCollectionFixture('users_collection.xml');
        $etalonRoutes = $this->loadEtalonRoutesInfo('users_collection.yml');

        foreach ($etalonRoutes as $name => $params) {
            $route = $collection->get($name);
            $methods = $route->getMethods();

            $this->assertNotNull($route, $name);
            $this->assertSame($params['path'], $route->getPath(), $name);
            $this->assertSame($params['methods'][0], $methods[0], $name);
            $this->assertStringContainsString($params['controller'], $route->getDefault('_controller'), $name);
        }
    }

    /**
     * Test that XML collection with custom prefixes gets parsed correctly.
     */
    public function testPrefixedUsersFixture()
    {
        $collection = $this->loadFromXmlCollectionFixture('prefixed_users_collection.xml');
        $etalonRoutes = $this->loadEtalonRoutesInfo('prefixed_users_collection.yml');

        foreach ($etalonRoutes as $name => $params) {
            $route = $collection->get($name);
            $methods = $route->getMethods();

            $this->assertNotNull($route, $name);
            $this->assertSame($params['path'], $route->getPath(), $name);
            $this->assertSame($params['methods'][0], $methods[0], $name);
            $this->assertStringContainsString($params['controller'], $route->getDefault('_controller'), $name);
        }
    }

    public function testManualRoutes()
    {
        $collection = $this->loadFromXmlCollectionFixture('routes.xml');
        $route = $collection->get('get_users');

        $this->assertSame('/users.{_format}', $route->getPath());
        $this->assertSame('json|xml|html', $route->getRequirement('_format'));
        $this->assertSame('RestRoutingBundle:UsersController:getUsers', $route->getDefault('_controller'));
    }

    public function testManualRoutesWithoutIncludeFormat()
    {
        $collection = $this->loadFromXmlCollectionFixture('routes.xml', false);
        $route = $collection->get('get_users');

        $this->assertSame('/users', $route->getPath());
    }

    public function testManualRoutesWithFormats()
    {
        $collection = $this->loadFromXmlCollectionFixture(
            'routes.xml',
            true,
            [
                'json' => false,
            ]
        );
        $route = $collection->get('get_users');

        $this->assertSame('json', $route->getRequirement('_format'));
    }

    public function testManualRoutesWithDefaultFormat()
    {
        $collection = $this->loadFromXmlCollectionFixture(
            'routes.xml',
            true,
            [
                'json' => false,
                'xml' => false,
                'html' => true,
            ],
            'xml'
        );
        $route = $collection->get('get_users');

        $this->assertSame('xml', $route->getDefault('_format'));
    }

    public function testForwardOptionsRequirementsAndDefaults()
    {
        $collection = $this->loadFromXmlCollectionFixture('routes_with_options_requirements_and_defaults.xml');

        foreach ($collection as $route) {
            $this->assertTrue((bool) $route->getOption('expose'));
            $this->assertSame('[a-z]+', $route->getRequirement('slug'));
            $this->assertSame('home', $route->getDefault('slug'));
        }
    }

    /**
     * Load routes collection from XML fixture routes under Tests\Fixtures directory.
     *
     * @param string   $fixtureName   name of the class fixture
     * @param bool     $includeFormat whether or not the requested view format must be included in the route path
     * @param string[] $formats       supported view formats
     * @param string   $defaultFormat default view format
     *
     * @return RouteCollection
     */
    protected function loadFromXmlCollectionFixture(
        $fixtureName,
        $includeFormat = true,
        array $formats = [
            'json' => false,
            'xml' => false,
            'html' => true,
        ],
        $defaultFormat = null
    ) {
        $collectionLoader = new RestXmlCollectionLoader(
            new FileLocator([__DIR__ . '/../../Fixtures/Routes']),
            new RestRouteProcessor(),
            $includeFormat,
            $formats,
            $defaultFormat
        );
        $controllerLoader = $this->getControllerLoader();

        new LoaderResolver([$collectionLoader, $controllerLoader]);

        return $collectionLoader->load($fixtureName, 'rest');
    }

    public function testHostnameFixture()
    {
        $collection = $this->loadFromXmlCollectionFixture('routes.xml');
        $route = $collection->get('get_users');

        $this->assertSame('rest.local', $route->getHost());
    }
}
