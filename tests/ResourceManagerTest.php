<?php

namespace Packaged\Dispatch\Tests;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\ResourceManager;
use Packaged\Dispatch\ResourceStore;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\DemoComponent;
use Packaged\Helpers\Path;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ResourceManagerTest extends TestCase
{

  public function testVendor()
  {
    $manager = ResourceManager::vendor('packaged', 'dispatch');
    $this->assertEquals(ResourceManager::MAP_VENDOR, $manager->getMapType());
    $this->assertEquals(['packaged', 'dispatch'], $manager->getMapOptions());
  }

  public function testAlias()
  {
    $manager = ResourceManager::alias('dispatch');
    $this->assertEquals(ResourceManager::MAP_ALIAS, $manager->getMapType());
    $this->assertEquals(['dispatch'], $manager->getMapOptions());
  }

  public function testResources()
  {
    $manager = ResourceManager::resources();
    $this->assertEquals(ResourceManager::MAP_RESOURCES, $manager->getMapType());
    $this->assertEquals([], $manager->getMapOptions());
  }

  public function testPublic()
  {
    $manager = ResourceManager::public();
    $this->assertEquals(ResourceManager::MAP_PUBLIC, $manager->getMapType());
    $this->assertEquals([], $manager->getMapOptions());
  }

  public function testComponent()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    $component = new DemoComponent();
    $manager = ResourceManager::component($component);
    $this->assertEquals(ResourceManager::MAP_COMPONENT, $manager->getMapType());
    $this->assertEquals(
      [6, 'Packaged', 'Dispatch', 'Tests', 'TestComponents', 'DemoComponent', 'DemoComponent'],
      $manager->getMapOptions()
    );
    $this->assertEquals(
      'c/6/Packaged/Dispatch/Tests/TestComponents/DemoComponent/DemoComponent/1a9ffb748d31/style.css',
      $manager->getResourceUri('style.css')
    );
    Dispatch::instance()->addComponentAlias('\Packaged\Dispatch\Tests\TestComponents', '');
    $manager = ResourceManager::component($component);
    $this->assertEquals(
      'c/3/_/DemoComponent/DemoComponent/1a9ffb748d31/style.css',
      $manager->getResourceUri('style.css')
    );
    Dispatch::destroy();
    $this->expectException(RuntimeException::class);
    ResourceManager::component($component);
  }

  public function testRequireJs()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    ResourceManager::resources()->requireJs('js/alert.js');
    $this->assertContains(
      'src="r/f417133ec50f/js/alert.js"',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_JS)
    );
  }

  public function testUniqueRequire()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    ResourceManager::resources()->requireJs('js/alert.js');
    $this->assertCount(1, Dispatch::instance()->store()->getResources(ResourceStore::TYPE_JS));
    ResourceManager::resources()->includeJs('js/alert.js');
    ResourceManager::resources()->requireJs('js/alert.js');
    $this->assertCount(1, Dispatch::instance()->store()->getResources(ResourceStore::TYPE_JS));
    ResourceManager::resources()->requireJs('js/misc.js');
    $this->assertCount(2, Dispatch::instance()->store()->getResources(ResourceStore::TYPE_JS));
  }

  public function testRequireCss()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    ResourceManager::resources()->includeCss('css/test.css');
    ResourceManager::resources()->requireCss('css/test.css');
    $this->assertContains(
      'href="r/bd04a6113c11/css/test.css"',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_CSS)
    );
  }

  public function testRequireInlineJs()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    ResourceManager::inline()->requireJs("alert('inline');");
    $this->assertEquals(
      '<script>alert(\'inline\');</script>',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_JS)
    );
    ResourceManager::inline()->requireJs("alert('priority');", null, ResourceStore::PRIORITY_HIGH);
    $this->assertEquals(
      '<script>alert(\'priority\');</script><script>alert(\'inline\');</script>',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_JS)
    );
  }

  public function testIsExternalUrl()
  {
    $manager = ResourceManager::external();
    $this->assertTrue($manager->isExternalUrl('http://www.google.com'));
    $this->assertTrue($manager->isExternalUrl('hTTp://www.google.com'));
    $this->assertFalse($manager->isExternalUrl('abhttp://www.google.com'));
    $this->assertTrue($manager->isExternalUrl('https://www.google.com'));
    $this->assertTrue($manager->isExternalUrl('httPS://www.google.com'));
    $this->assertFalse($manager->isExternalUrl('abhttps://www.google.com'));
    $this->assertTrue($manager->isExternalUrl('//www.google.com'));
    $this->assertFalse($manager->isExternalUrl('://www.google.com'));

    //Check external still work on other resource types
    $manager = ResourceManager::public();
    $this->assertTrue($manager->isExternalUrl('http://www.google.com'));
    $this->assertFalse($manager->isExternalUrl('abhttp://www.google.com'));
  }

  public function testGetFilePath()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    $this->assertEquals(
      Path::system(__DIR__, '_root', 'public', 'placeholder.html'),
      ResourceManager::public()->getFilePath('placeholder.html')
    );
  }

  public function testMissingFile()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    $component = new DemoComponent();
    $manager = ResourceManager::component($component);
    $manager->setOption(ResourceManager::OPT_THROW_ON_FILE_NOT_FOUND, false);
    $this->assertNull($manager->getResourceUri('style.missing.css'));
    $manager->setOption(ResourceManager::OPT_THROW_ON_FILE_NOT_FOUND, true);
    $manager->includeCss('style.missing.css');
    $manager->includeJs('script.missing.js');
    $this->expectExceptionCode(404);
    $this->expectException(RuntimeException::class);
    $manager->getResourceUri('style.missing.css');
  }

  public function testGetFileHash()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    //Test cache code (apcu)
    for($i = 0; $i < 3; $i++)
    {
      $this->assertEquals(
        "d91424cc",
        ResourceManager::resources()->getFileHash(Path::system(__DIR__, '_root', 'public', 'placeholder.html'))
      );
    }
  }

  public function testRequireInlineCss()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    ResourceManager::inline()->requireCss("body{background:green;}");
    $this->assertEquals(
      '<style>body{background:green;}</style>',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_CSS)
    );
    ResourceManager::inline()->requireCss("body{background:red;}", null, ResourceStore::PRIORITY_HIGH);
    $this->assertEquals(
      '<style>body{background:red;}</style><style>body{background:green;}</style>',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_CSS)
    );
  }

  public function testRelativeHash()
  {
    Dispatch::bind(new Dispatch(Path::system(__DIR__, '_root')));
    $pathHash = Dispatch::instance()->generateHash('resources/css/test.css', 4);
    $manager = ResourceManager::resources();
    $resourceHash = $manager->getRelativeHash($manager->getFilePath('css/test.css'));
    $this->assertEquals($pathHash, $resourceHash);

    $pathHash = Dispatch::instance()->generateHash('public/favicon.ico', 4);
    $manager = ResourceManager::public();
    $resourceHash = $manager->getRelativeHash($manager->getFilePath('favicon.ico'));
    $this->assertEquals($pathHash, $resourceHash);
  }

  public function testSetResourceStore()
  {
    $store1 = new ResourceStore();
    $store2 = new ResourceStore();

    $manager = ResourceManager::resources();
    $this->assertFalse(ResourceManager::resources()->hasResourceStore());
    $this->assertFalse($manager->hasResourceStore());

    $manager->setResourceStore($store1);
    $this->assertSame($store1, $manager->getResourceStore());
    $this->assertNotSame($store2, $manager->getResourceStore());

    $this->assertTrue($manager->hasResourceStore());

    $manager = ResourceManager::resources()->setResourceStore($store2);
    $this->assertSame($store2, $manager->getResourceStore());
    $this->assertNotSame($store1, $manager->getResourceStore());
  }

  public function testSetResourceStoreConfig()
  {
    $store1 = new ResourceStore();
    $store2 = new ResourceStore();

    $this->assertFalse(ResourceManager::resources()->hasResourceStore());

    $manager = ResourceManager::resources([ResourceManager::OPT_RESOURCE_STORE => $store1]);
    $this->assertTrue($manager->hasResourceStore());

    $manager->useGlobalResourceStore();
    $this->assertFalse($manager->hasResourceStore());

    $manager->setOption(ResourceManager::OPT_RESOURCE_STORE, $store1);
    $this->assertSame($store1, $manager->getResourceStore());
    $this->assertNotSame($store2, $manager->getResourceStore());
  }
}
