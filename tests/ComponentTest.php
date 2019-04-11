<?php

namespace Packaged\Dispatch\Tests;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\ResourceManager;
use Packaged\Dispatch\ResourceStore;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\Child\ChildComponent;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\Child\ChildDemoComponent;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\Child\FixedChildDemoComponent;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\DemoComponent;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\ResourcedDemoComponent;
use Packaged\Helpers\Path;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ComponentTest extends TestCase
{
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
    $this->assertEquals('c/3/_/DemoComponent/DemoComponent/1a9ffb748d31/style.css', $uri);

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
    $this->assertEquals('c/2/_DC/DemoComponent/1a9ffb748d31/style.css', $uri);

    $request = Request::create('/c/3/_/MissingComponent/DemoComponent/a4197ed8/style.css');
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertContains('Component Not Found', $response->getContent());

    $manager = ResourceManager::component(new ChildComponent());
    $uri = $manager->getResourceUri('style.css');
    $this->assertEquals('c/2/_/AbstractComponent/162fe246c68b/style.css', $uri);

    $request = Request::create('/' . $uri);
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(
      '@import "c/2/_/AbstractComponent/942e325b3dcc/dependency.css";body{color:blue;background:url("c/2/_/AbstractComponent/395d1a0e845f/img/x.jpg")}',
      $response->getContent()
    );
  }

  public function testResourceExtension()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'));
    Dispatch::bind($dispatch);

    $demoComponent = new DemoComponent();
    $childComponent = new ChildDemoComponent();

    $response = $dispatch->handleRequest(Request::create('/' . $demoComponent->getContentFile()));
    $this->assertStringStartsWith("Parent Content", $response->getContent());

    $response = $dispatch->handleRequest(Request::create('/' . $childComponent->getContentFile()));
    $this->assertStringStartsWith("Child Content", $response->getContent());

    $response = $dispatch->handleRequest(Request::create('/' . $demoComponent->getParentFile()));
    $this->assertStringStartsWith("Parent Only", $response->getContent());

    $response = $dispatch->handleRequest(Request::create('/' . $childComponent->getParentFile()));
    $this->assertStringStartsWith("Parent Only", $response->getContent());
  }

  public function testFixedResourceExtensionException()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'));
    Dispatch::bind($dispatch);
    $fixedChildComponent = new FixedChildDemoComponent();
    $this->expectException(RuntimeException::class);
    $dispatch->handleRequest(Request::create('/' . $fixedChildComponent->getParentFile()));
  }

  public function testResourceExtensionException()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'));
    Dispatch::bind($dispatch);
    $childComponent = new ChildDemoComponent();
    $this->expectException(RuntimeException::class);
    $dispatch->handleRequest(Request::create('/' . $childComponent->getParentFile(false)));
  }
}
