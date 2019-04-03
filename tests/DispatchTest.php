<?php

namespace Packaged\Dispatch\Tests;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\ResourceManager;
use Packaged\Dispatch\ResourceStore;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\Child\ChildComponent;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\DemoComponent;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\ResourcedDemoComponent;
use Packaged\Helpers\Path;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class DispatchTest extends TestCase
{
  public function testInstance()
  {
    $dispatch = new Dispatch(__DIR__);
    $this->assertNull(Dispatch::instance());
    Dispatch::bind($dispatch);
    $this->assertSame($dispatch, Dispatch::instance());
    Dispatch::destroy();
    $this->assertNull(Dispatch::instance());
    $this->assertEquals(Path::system(__DIR__, Dispatch::RESOURCES_DIR), $dispatch->getResourcesPath());
    $this->assertEquals(Path::system(__DIR__, Dispatch::PUBLIC_DIR), $dispatch->getPublicPath());
    $this->assertEquals(Path::system(__DIR__, Dispatch::VENDOR_DIR, 'a', 'b'), $dispatch->getVendorPath('a', 'b'));
  }

  public function testAlias()
  {
    $dispatch = new Dispatch(__DIR__);
    $this->assertNull($dispatch->getAliasPath('abc'));
    $dispatch->addAlias('abc', 'a/b/c');
    $this->assertEquals(Path::system(__DIR__, 'a/b/c'), $dispatch->getAliasPath('abc'));
  }

  public function testHandle()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'));
    Dispatch::bind($dispatch);

    $request = Request::create('/placeholder.html');
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(404, $response->getStatusCode());

    $request = Request::create('/r/e69b7aXX/css/test.css');
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(404, $response->getStatusCode());

    $request = Request::create('/r/e69b7aabcde/css/test.css');
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(404, $response->getStatusCode());

    $request = Request::create('/r/bd04a611ed6d/css/test.css');
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertContains('url(r/395d1a0e1a36/img/x.jpg)', $response->getContent());

    $uri = ResourceManager::public()->getResourceUri('css/placeholder.css');
    $request = Request::create($uri);
    $response = $dispatch->handleRequest($request);
    $this->assertContains('font-size:14px', $response->getContent());

    $dispatch->addAlias('abc', 'resources/css');
    $request = Request::create('/a/abc/bd04a611ed6d/test.css');
    $response = $dispatch->handleRequest($request);
    $this->assertContains('url("a/abc/942e325be1fb/sub/subimg.jpg")', $response->getContent());

    $uri = ResourceManager::vendor('packaged', 'dispatch')->getResourceUri('css/vendor.css');
    $request = Request::create($uri);
    $response = $dispatch->handleRequest($request);
    $this->assertContains('body{background:orange}', $response->getContent());

    Dispatch::destroy();
  }

  public function testBaseUri()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'), 'http://assets.packaged.in');
    Dispatch::bind($dispatch);
    $request = Request::create('/r/bd04a611ed6d/css/test.css');
    $response = $dispatch->handleRequest($request);
    $this->assertContains('url(http://assets.packaged.in/r/395d1a0e1a36/img/x.jpg)', $response->getContent());
    Dispatch::destroy();
  }

  public function testStore()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root'), 'http://assets.packaged.in'));
    ResourceManager::resources()->requireCss('css/test.css');
    ResourceManager::resources()->requireCss('css/do-not-modify.css');
    $response = Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_CSS);
    $this->assertContains('href="http://assets.packaged.in/r/bd04a611ed6d/css/test.css"', $response);
    ResourceManager::resources()->requireJs('js/alert.js');
    $response = Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_JS);
    $this->assertContains('src="http://assets.packaged.in/r/f417133e49d9/js/alert.js"', $response);
    Dispatch::destroy();
  }

  /**
   * @throws \Exception
   */
  public function testComponent()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'));
    Dispatch::bind($dispatch);

    $component = new DemoComponent();
    Dispatch::instance()->addComponentAlias('\Packaged\Dispatch\Tests\TestComponents', '');
    $manager = ResourceManager::component($component);
    $uri = $manager->getResourceUri('style.css');
    $this->assertEquals('c/3/_/DemoComponent/DemoComponent/1a9ffb74839e/style.css', $uri);

    $request = Request::create('/' . $uri);
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertContains('body{color:red}', $response->getContent());

    Dispatch::instance()->store()->clearStore(ResourceStore::TYPE_CSS);
    $this->assertCount(0, Dispatch::instance()->store()->getResources(ResourceStore::TYPE_CSS));
    $resourceComponent = new ResourcedDemoComponent();
    $this->assertCount(1, Dispatch::instance()->store()->getResources(ResourceStore::TYPE_CSS));
    $manager = ResourceManager::component($resourceComponent);
    $request = Request::create('/' . $manager->getResourceUri('style.css'));
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertContains('body{color:red}', $response->getContent());

    //Required for testing correct namespace validation
    Dispatch::instance()->addComponentAlias('\Packaged\Dispatch\Tests\TestComponents\DemoComponent', 'DC');
    Dispatch::instance()->addComponentAlias('\Packaged\Dispatch\Tests\TestComponents\DemoComponents', 'DCRC');
    $manager = ResourceManager::component(new DemoComponent());
    $uri = $manager->getResourceUri('style.css');
    $this->assertEquals('c/2/_DC/DemoComponent/1a9ffb74839e/style.css', $uri);

    $request = Request::create('/c/3/_/MissingComponent/DemoComponent/a4197ed8/style.css');
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertContains('Component Not Found', $response->getContent());

    $manager = ResourceManager::component(new ChildComponent());
    $uri = $manager->getResourceUri('style.css');
    $this->assertEquals('c/2/_/AbstractComponent/162fe246a4b7/style.css', $uri);

    $request = Request::create('/' . $uri);
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(
      '@import "c/2/_/AbstractComponent/942e325bd433/dependency.css";body{color:blue;background:url("c/2/_/AbstractComponent/395d1a0eef92/img/x.jpg")}',
      $response->getContent()
    );
  }

  public function testImport()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'));
    Dispatch::bind($dispatch);

    Dispatch::instance()->addComponentAlias('\Packaged\Dispatch\Tests\TestComponents\AbstractComponent', '');
    $manager = ResourceManager::component(new ChildComponent());
    $uri = $manager->getResourceUri('import.js');
    $this->assertEquals('c/1/_/831aff3198a1/import.js', $uri);

    $request = Request::create($uri);
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(
      'import"c/1/_/942e325bd433/dependency.css";import"c/1/_/942e325b0845/dependency.js";import"c/1/_/942e325b0845/dependency.js";',
      $response->getContent()
    );
  }

  public function testHashing()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'));
    Dispatch::bind($dispatch);
    $uri = ResourceManager::public()->getResourceUri('css/placeholder.css');
    $this->assertEquals($uri, ResourceManager::public()->getResourceUri('css/placeholder.css'));
    $dispatch->setHashSalt('abc');
    $this->assertNotEquals($uri, ResourceManager::public()->getResourceUri('css/placeholder.css'));
  }
}
