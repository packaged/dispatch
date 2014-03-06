<?php

class AssetManagerTest extends PHPUnit_Framework_TestCase
{
  public function testStaticBuilders()
  {
    $manager = \Packaged\Dispatch\AssetManager::aliasType('alias');
    $this->assertInstanceOf('\Packaged\Dispatch\AssetManager', $manager);
    $manager = \Packaged\Dispatch\AssetManager::assetType();
    $this->assertInstanceOf('\Packaged\Dispatch\AssetManager', $manager);
    $manager = \Packaged\Dispatch\AssetManager::sourceType();
    $this->assertInstanceOf('\Packaged\Dispatch\AssetManager', $manager);
    $manager = \Packaged\Dispatch\AssetManager::vendorType('pckaged', 'config');
    $this->assertInstanceOf('\Packaged\Dispatch\AssetManager', $manager);

    $this->assertNull($manager->getResourceUri('missing.png'));
  }

  public function testStore()
  {
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->headers->set('HOST', 'www.packaged.in');
    $request->server->set('REQUEST_URI', '/');
    $opts       = ['assets_dir' => 'asset'];
    $opt        = new \Packaged\Config\Provider\ConfigSection('', $opts);
    $dispatcher = new \Packaged\Dispatch\Dispatch(new DummyKernel(), $opt);
    $dispatcher->setBaseDirectory(__DIR__);
    $dispatcher->handle($request);
    $manager = \Packaged\Dispatch\AssetManager::assetType();
    $manager->requireCss('test', ['delay' => true]);
    $manager->requireJs('test');
    $this->assertEquals(
      [
        '//www.packaged.in/res/p/8cac7/b/edc2182/test.css' => ['delay' => true]
      ],
      \Packaged\Dispatch\AssetManager::getUrisByType('css')
    );
  }

  public function testConstructException()
  {
    //Ensure a valid constructor does not throw an exception
    new \Packaged\Dispatch\AssetManager(
      new \Packaged\Config\Provider\ConfigSection()
    );
    $this->setExpectedException(
      '\Exception',
      "You cannot construct an asset manager without specifying " .
      "either a callee or forceType"
    );
    new \Packaged\Dispatch\AssetManager('hello');
  }

  /**
   * @dataProvider mapTypeProvider
   *
   * @param $callee
   * @param $expect
   */
  public function testMapTypes($callee, $expect)
  {
    $manager = new AssetManagerTester($callee);
    $this->assertEquals($expect, $manager->getMapType());
    $this->assertEquals($expect, $manager->lookupMapType($callee));
  }

  public function mapTypeProvider()
  {
    $vendorCallee = new \Symfony\Component\HttpKernel\UriSigner("d");
    return [
      [$this, \Packaged\Dispatch\DirectoryMapper::MAP_SOURCE],
      [$vendorCallee, \Packaged\Dispatch\DirectoryMapper::MAP_VENDOR],
      [
        new \Packaged\Config\Provider\ConfigSection(),
        \Packaged\Dispatch\DirectoryMapper::MAP_VENDOR
      ],
    ];
  }
}

class AssetManagerTester extends \Packaged\Dispatch\AssetManager
{
  protected function ownFile()
  {
    return dirname(__DIR__) . '/vendor/packaged/dispatch/src/AssetManager.php';
  }
}
