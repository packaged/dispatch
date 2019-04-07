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
    $this->assertContains(
      'alert(\'inline\');',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_JS)
    );
  }

  public function testIsExternalUrl()
  {
    $manager = ResourceManager::external();
    $this->assertTrue($manager->isExternalUrl('http://www.google.com'));
    $this->assertFalse($manager->isExternalUrl('abhttp://www.google.com'));

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
    $this->assertContains(
      'body{background:green;}',
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
}
