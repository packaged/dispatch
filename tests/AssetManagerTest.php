<?php

use Packaged\Helpers\Path;

class AssetManagerTest extends \PHPUnit\Framework\TestCase
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

  public function testUnfoundAssets()
  {
    $mgr = \Packaged\Dispatch\AssetManager::assetType();
    $mgr->clearStore();
    $mgr->requireJs('this/doesnt/exist');
    $mgr->requireCss('this/doesnt/exist/either');
    $js = \Packaged\Dispatch\AssetManager::getUrisByType(
      \Packaged\Dispatch\AssetManager::TYPE_JS
    );
    $this->assertEmpty($js);
    $css = \Packaged\Dispatch\AssetManager::getUrisByType(
      \Packaged\Dispatch\AssetManager::TYPE_CSS
    );
    $this->assertEmpty($css);
  }

  public function testStore()
  {
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->headers->set('HOST', 'www.packaged.in');
    $request->server->set('REQUEST_URI', '/');
    $opts = ['assets_dir' => 'asset'];
    $opt = new \Packaged\Config\Provider\ConfigSection('', $opts);
    $dispatcher = new \Packaged\Dispatch\Dispatch(new DummyKernel(), $opt);
    $dispatcher->setBaseDirectory(__DIR__);
    $dispatcher->handle($request);
    $manager = \Packaged\Dispatch\AssetManager::assetType();
    $manager->clearStore();
    $manager->requireCss('test', ['delay' => true]);
    $manager->requireJs('test');

    $this->assertEquals(
      [
        '//www.packaged.in/res/p/8cac7/b/76d6c18/test.css' => ['delay' => true],
      ],
      \Packaged\Dispatch\AssetManager::getUrisByType('css')
    );

    $this->assertEquals(
      [
        '//www.packaged.in/res/p/8cac7/b/e2218e4/test.js' => null,
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
    $opts = ['assets_dir' => 'asset'];
    $opt = new \Packaged\Config\Provider\ConfigSection('', $opts);
    $dispatcher = new \Packaged\Dispatch\Dispatch(new DummyKernel(), $opt);
    $dispatcher->setBaseDirectory(__DIR__);
    $dispatcher->handle($request);
    $manager = \Packaged\Dispatch\AssetManager::assetType();
    $manager->requireCss('test');
    $manager->requireJs('test', ['delay' => true]);
    $manager->requireJs('tests');
    $manager->requireJs('tests.min');
    $manager->requireJs('testnotfound', ['delay' => true]);

    $this->assertEquals(
      '<link href="//www.packaged.in/res/p/8cac7/b/76d6c18/test.css"' .
      ' rel="stylesheet" type="text/css">',
      \Packaged\Dispatch\AssetManager::generateHtmlIncludes('css')
    );

    $this->assertEquals(
      '<script src="//www.packaged.in/res/p/8cac7/b/e2218e4/test.js"' .
      ' delay="true"></script>'
      . '<script src="//www.packaged.in/res/p/8cac7/b/9b0a055/tests.min.js">'
      . '</script>',
      \Packaged\Dispatch\AssetManager::generateHtmlIncludes('js')
    );
    $this->assertEquals(
      '',
      \Packaged\Dispatch\AssetManager::generateHtmlIncludes('fnt')
    );
  }

  public function testGenerateHtmlIncludesInline()
  {
    $manager = \Packaged\Dispatch\AssetManager::assetType();
    $manager->clearStore();
    $manager->requireInlineCss('body{background: red:}');
    $manager->requireInlineJs('alert(\'Testing\');');

    $this->assertEquals(
      '<style>body{background: red:}</style>',
      \Packaged\Dispatch\AssetManager::generateHtmlIncludes('css')
    );

    $this->assertEquals(
      '<script>alert(\'Testing\');</script>',
      \Packaged\Dispatch\AssetManager::generateHtmlIncludes('js')
    );
  }

  public function testConstructException()
  {
    //Ensure a valid constructor does not throw an exception
    new \Packaged\Dispatch\AssetManager(
      new \Packaged\Config\Provider\ConfigSection()
    );
    $this->expectException(
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
        \Packaged\Dispatch\DirectoryMapper::MAP_VENDOR,
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
        ['packaged', 'dispatch'],
      ],
    ];
  }

  /**
   * @dataProvider vendorPackageProvider
   *
   * @param $projectDir
   * @param $vendor
   * @param $package
   */
  public function testVendorDetectedByDirectory($projectDir, $vendor, $package)
  {
    $root = sys_get_temp_dir() . '/' . $projectDir . '-' . uniqid();
    mkdir($root, 0700);
    $root = realpath($root);
    $src = Path::build($root, 'vendor', $vendor, $package, 'src');
    mkdir($src, 0700, true);
    $class = 'VendorPathWidget' . uniqid();
    file_put_contents(
      Path::build($src, $class . '.php'), "<?php class $class {}"
    );
    try
    {
      require Path::build($src, $class . '.php');
      FixedPathAssetManager::$ownFile = Path::build(
        $root, 'vendor', 'packaged', 'dispatch', 'src', 'AssetManager.php'
      );
      $manager = new FixedPathAssetManager(new $class());
      $this->assertEquals(
        \Packaged\Dispatch\DirectoryMapper::MAP_VENDOR,
        $manager->getMapType()
      );
      $this->assertEquals([$vendor, $package], $manager->getLookupParts());
    }
    finally
    {
      unlink(Path::build($src, $class . '.php'));
      for($dir = $src; $dir !== dirname($root); $dir = dirname($dir))
      {
        rmdir($dir);
      }
    }
  }

  public function vendorPackageProvider()
  {
    return [
      ['dispatch-app', 'acme', 'widget'],
      //Digits in the project path
      ['dispatch-release-20260928', 'acme', 'widget'],
      //Names sharing leading characters with packaged/dispatch
      ['dispatch-app', 'paypal', 'sdk'],
      ['dispatch-app', 'packaged', 'dal'],
    ];
  }

  public function testExternalResource()
  {
    $am = \Packaged\Dispatch\AssetManager::sourceType();
    $location = 'http://test.com/css.css';
    $this->assertEquals($location, $am->getResourceUri($location));
  }
}

class AssetManagerTester extends \Packaged\Dispatch\AssetManager
{
  protected function ownFile()
  {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . Path::build(
        'vendor',
        'packaged',
        'dispatch',
        'src',
        'AssetManager.php'
      );
  }
}

class FixedPathAssetManager extends \Packaged\Dispatch\AssetManager
{
  public static $ownFile;

  protected function ownFile()
  {
    return static::$ownFile;
  }
}
