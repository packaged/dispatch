<?php

namespace Packaged\Dispatch\Tests;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\ResourceManager;
use Packaged\Dispatch\ResourceStore;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\Child\ChildComponent;
use Packaged\Helpers\Path;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class DispatchTest extends TestCase
{
  public function testInstance()
  {
    Dispatch::destroy();
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
    $this->assertContains('url(r/395d1a0e8999/img/x.jpg)', $response->getContent());

    $uri = ResourceManager::public()->getResourceUri('css/placeholder.css');
    $request = Request::create($uri);
    $response = $dispatch->handleRequest($request);
    $this->assertContains('font-size:14px', $response->getContent());
    $this->assertContains('background:url(p/942e325b1780/img/test.svg#test)', $response->getContent());

    $dispatch->addAlias('abc', 'resources/css');
    $request = Request::create('/a/abc/bd04a611ed6d/test.css');
    $response = $dispatch->handleRequest($request);
    $this->assertContains('url("a/abc/942e325be95f/sub/subimg.jpg")', $response->getContent());

    $uri = ResourceManager::vendor('packaged', 'dispatch')->getResourceUri('css/vendor.css');
    $request = Request::create($uri);
    $response = $dispatch->handleRequest($request);
    $this->assertContains('body{background:orange}', $response->getContent());

    Dispatch::instance()->config()->addItem('ext.css', 'sourcemap', true);
    $uri = ResourceManager::vendor('packaged', 'dispatch')->getResourceUri('css/vendor.css');
    $request = Request::create($uri);
    $response = $dispatch->handleRequest($request);
    $this->assertContains('sourceMappingURL', $response->getContent());
    $this->assertContains('Q1NTLU1BUAo', $response->getContent());

    Dispatch::destroy();
  }

  public function testBaseUri()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'), 'http://assets.packaged.in');
    Dispatch::bind($dispatch);
    $request = Request::create('/r/bd04a611ed6d/css/test.css');
    $response = $dispatch->handleRequest($request);
    $this->assertContains('url(http://assets.packaged.in/r/395d1a0e8999/img/x.jpg)', $response->getContent());
    Dispatch::destroy();
  }

  public function testStore()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root'), 'http://assets.packaged.in'));
    ResourceManager::resources()->requireCss('css/test.css');
    ResourceManager::resources()->requireCss('css/do-not-modify.css');
    $response = Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_CSS);
    $this->assertContains('href="http://assets.packaged.in/r/bd04a6113c11/css/test.css"', $response);
    ResourceManager::resources()->requireJs('js/alert.js');
    $response = Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_JS);
    $this->assertContains('src="http://assets.packaged.in/r/f417133ec50f/js/alert.js"', $response);
    Dispatch::destroy();
  }

  public function testImport()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'));
    Dispatch::bind($dispatch);

    Dispatch::instance()->addComponentAlias('\Packaged\Dispatch\Tests\TestComponents\AbstractComponent', '');
    $manager = ResourceManager::component(new ChildComponent());
    $uri = $manager->getResourceUri('import.js');
    $this->assertEquals('c/1/_/831aff315092/import.js', $uri);

    $request = Request::create($uri);
    $response = $dispatch->handleRequest($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(
      'import"c/1/_/942e325b3dcc/dependency.css";import"c/1/_/942e325b4521/dependency.js";import"c/1/_/942e325b4521/dependency.js";',
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
    $this->assertEquals(substr($dispatch->generateHash('abc'), 0, 8), $dispatch->generateHash('abc', 8));
  }

  public function testSetResourceStore()
  {
    $store1 = new ResourceStore();
    $store2 = new ResourceStore();

    $dispatch = new Dispatch(__DIR__);

    $dispatch->setResourceStore($store1);
    $this->assertSame($store1, $dispatch->store());
    $this->assertNotSame($store2, $dispatch->store());

    $dispatch->setResourceStore($store2);
    $this->assertSame($store2, $dispatch->store());
    $this->assertNotSame($store1, $dispatch->store());
  }

  public function testWebpReplacements()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'), 'http://assets.packaged.in');
    Dispatch::bind($dispatch);
    Dispatch::instance()->setAcceptableContentTypes(['image/webp']);

    $request = Request::create(ResourceManager::resources()->getResourceUri('css/webptest.css'));
    $response = $dispatch->handleRequest($request);
    $this->assertContains(
      'url(http://assets.packaged.in/r/30c60da9f504/img/test-sample.png?abc=def#xyz)',
      $response->getContent()
    );
    $this->assertNotContains(
      'url(http://assets.packaged.in/r/30c60da9f504/img/test-sample.png.webp?abc=def#xyz)',
      $response->getContent()
    );

    //Enable WebP
    Dispatch::instance()->config()->addItem('optimisation', 'webp', true);
    Dispatch::instance()->setAcceptableContentTypes(['image/webp']);

    $request = Request::create(ResourceManager::resources()->getResourceUri('css/webptest.css'));
    $response = $dispatch->handleRequest($request);

    $this->assertNotContains(
      'url(http://assets.packaged.in/r/30c60da9f504-1/img/test-sample.png?abc=def#xyz)',
      $response->getContent()
    );
    $this->assertContains(
      'url(http://assets.packaged.in/r/d6e2937fee66-1/img/test-sample.png.webp?abc=def#xyz)',
      $response->getContent()
    );

    Dispatch::destroy();
  }

  public function testPdf()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'), 'http://assets.packaged.in');
    Dispatch::bind($dispatch);

    $request = Request::create(
      ResourceManager::resources()
        ->getResourceUri('css/webptest.css')
    );

    $response = $dispatch->handleRequest($request);
    $this->assertFalse($response->headers->has('Content-Disposition'));

    $request = Request::create(
      ResourceManager::resources()
        ->getResourceUri('css/webptest.css', true, Dispatch::FLAG_CONTENT_ATTACHMENT)
    );

    $response = $dispatch->handleRequest($request);
    $this->assertTrue($response->headers->has('Content-Disposition'));
    Dispatch::destroy();
  }

}
