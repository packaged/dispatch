<?php

namespace Packaged\Dispatch\Tests\Manager;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\Manager\ResourceManager;
use Packaged\Dispatch\ResourceStore;
use Packaged\Helpers\Path;
use PHPUnit\Framework\TestCase;

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

  public function testRequireJs()
  {
    Dispatch::bind(new Dispatch(Path::system(dirname(__DIR__),'_root')));
    ResourceManager::resources()->requireJs('js/alert.js');
    $this->assertContains(
      'src="r/ef6402a7/js/alert.js"',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_JS)
    );
  }

  public function testRequireCss()
  {
    Dispatch::bind(new Dispatch(Path::system(dirname(__DIR__),'_root')));
    ResourceManager::resources()->requireCss('css/test.css');
    $this->assertContains(
      'href="r/e69b7a20/css/test.css"',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_CSS)
    );
  }

  public function testRequireInlineJs()
  {
    Dispatch::bind(new Dispatch(Path::system(dirname(__DIR__),'_root')));
    ResourceManager::inline()->requireJs("alert('inline');");
    $this->assertContains(
      'alert(\'inline\');',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_JS)
    );
  }

  public function testIsExternalUrl()
  {
    $manager = ResourceManager::public();
    $this->assertTrue($manager->isExternalUrl('http://www.google.com'));
    $this->assertFalse($manager->isExternalUrl('abhttp://www.google.com'));
  }

  public function testGetFilePath()
  {
    Dispatch::bind(new Dispatch(Path::system(dirname(__DIR__),'_root')));
    $this->assertEquals(
      Path::system(dirname(__DIR__),'_root', 'public', 'placeholder.html'),
      ResourceManager::public()->getFilePath('placeholder.html')
    );
  }

  public function testRequireInlineCss()
  {
    Dispatch::bind(new Dispatch(Path::system(dirname(__DIR__),'_root')));
    ResourceManager::inline()->requireCss("body{background:green;}");
    $this->assertContains(
      'body{background:green;}',
      Dispatch::instance()->store()->generateHtmlIncludes(ResourceStore::TYPE_CSS)
    );
  }
}