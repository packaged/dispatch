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
    $this->assertNull($manager->getResourceUri(''));
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
    $manager->requireLess('test');
    $manager->requireScss('test');
    $manager->requireJs('test');

    $this->assertEquals(
      [
        '//www.packaged.in/res/p/8cac7/b/76d6c18/test.css'  => ['delay' => true],
        '//www.packaged.in/res/p/8cac7/b/2900bb5/test.less' => null,
        '//www.packaged.in/res/p/8cac7/b/d22435c/test.scss' => null,
      ],
      \Packaged\Dispatch\AssetManager::getUrisByType('css')
    );

    $this->assertEquals(
      [
        '//www.packaged.in/res/p/8cac7/b/e2218e4/test.js' => null
      ],
      \Packaged\Dispatch\AssetManager::getUrisByType('js')
    );

    $this->assertNotNull(\Packaged\Dispatch\AssetManager::getUrisByType('css'));
    $manager->clearStore('css');
    $this->assertEmpty(\Packaged\Dispatch\AssetManager::getUrisByType('css'));

    $this->assertNotNull(\Packaged\Dispatch\AssetManager::getUrisByType('js'));
    $manager->clearStore();
    $this->assertEmpty(\Packaged\Dispatch\AssetManager::getUrisByType('js'));
  }

  public function testGenerateHtmlIncludes()
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
    $manager->requireCss('test');
    $manager->requireJs('test', ['delay' => true]);
    $manager->requireJs('testnotfound', ['delay' => true]);

    $this->assertEquals(
      '<link href="//www.packaged.in/res/p/8cac7/b/76d6c18/test.css"' .
      ' rel="stylesheet" type="text/css">',
      \Packaged\Dispatch\AssetManager::generateHtmlIncludes('css')
    );

    $this->assertEquals(
      '<script src="//www.packaged.in/res/p/8cac7/b/e2218e4/test.js"' .
      ' delay="true"></script>',
      \Packaged\Dispatch\AssetManager::generateHtmlIncludes('js')
    );
    $this->assertEquals(
      '',
      \Packaged\Dispatch\AssetManager::generateHtmlIncludes('fnt')
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

  /**
   * @dataProvider buildUriProvider
   *
   * @param $uri
   * @param $mapType
   * @param $parts
   */
  public function testBuildFromUri($uri, $mapType, $parts)
  {
    $am = \Packaged\Dispatch\AssetManager::buildFromUri($uri);
    if($mapType === null)
    {
      $this->assertNull($am);
    }
    else
    {
      $this->assertEquals($mapType, $am->getMapType());
      $this->assertEquals($parts, $am->getLookupParts());
    }
  }

  public function buildUriProvider()
  {
    return [
      ["gh/sdf", null, null],
      ["a/b/c", \Packaged\Dispatch\DirectoryMapper::MAP_ALIAS, ['b']],
      ["s/na/c", \Packaged\Dispatch\DirectoryMapper::MAP_SOURCE, []],
      ["p/na/c", \Packaged\Dispatch\DirectoryMapper::MAP_ASSET, []],
      [
        "v/packaged/dispatch",
        \Packaged\Dispatch\DirectoryMapper::MAP_VENDOR,
        ['packaged', 'dispatch']
      ],
    ];
  }

  public function testExternalResource()
  {
    $am       = \Packaged\Dispatch\AssetManager::sourceType();
    $location = 'http://test.com/css.css';
    $this->assertEquals($location, $am->getResourceUri($location));
  }
}

class AssetManagerTester extends \Packaged\Dispatch\AssetManager
{
  protected function ownFile()
  {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . build_path(
      'vendor',
      'packaged',
      'dispatch',
      'src',
      'AssetManager.php'
    );
  }
}
