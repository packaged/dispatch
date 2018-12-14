<?php

namespace Packaged\Dispatch\Tests;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\ResourceManager;
use Packaged\Dispatch\ResourceStore;
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
    $response = $dispatch->handle($request);
    $this->assertEquals(404, $response->getStatusCode());

    $request = Request::create('/r/e69b7aXX/css/test.css');
    $response = $dispatch->handle($request);
    $this->assertEquals(404, $response->getStatusCode());

    $request = Request::create('/r/e69b7a20/css/test.css');
    $response = $dispatch->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertContains('url(\'r/d41d8cd9/img/x.jpg\')', $response->getContent());

    $request = Request::create('/p/d5dd9dc7/css/placeholder.css');
    $response = $dispatch->handle($request);
    $this->assertContains('font-size:14px', $response->getContent());

    $dispatch->addAlias('abc', 'resources/css');
    $request = Request::create('/a/abc/e69b7a20/test.css');
    $response = $dispatch->handle($request);
    $this->assertContains('url(\'a/abc/d41d8cd9/sub/subimg.jpg\')', $response->getContent());

    $request = Request::create('/v/packaged/dispatch/6673b7e0/css/vendor.css');
    $response = $dispatch->handle($request);
    $this->assertContains('body{background:orange}', $response->getContent());

    Dispatch::destroy();
  }

  public function testBaseUri()
  {
    $dispatch = new Dispatch(Path::system(__DIR__, '_root'), 'http://assets.packaged.in');
    Dispatch::bind($dispatch);
    $request = Request::create('/r/e69b7a20/css/test.css');
    $response = $dispatch->handle($request);
    $this->assertContains('url(\'http://assets.packaged.in/r/d41d8cd9/img/x.jpg\')', $response->getContent());
    Dispatch::destroy();
  }

  public function testStore()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root'), 'http://assets.packaged.in'));
    ResourceManager::resources()->requireCss('css/test.css');
    ResourceManager::resources()->requireCss('css/do-not-modify.css');
    $response = Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_CSS);
    $this->assertContains('href="http://assets.packaged.in/r/e69b7a20/css/test.css"', $response);
    ResourceManager::resources()->requireJs('js/alert.js');
    $response = Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_JS);
    $this->assertContains('src="http://assets.packaged.in/r/ef6402a7/js/alert.js"', $response);
    Dispatch::destroy();
  }
}
