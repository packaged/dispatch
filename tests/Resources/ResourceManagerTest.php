<?php

namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\Manager\ResourceManager;
use Packaged\Helpers\Path;
use PHPUnit\Framework\TestCase;

class ResourceManagerTest extends TestCase
{
  public function testResourceUri()
  {
    $root = Path::system(dirname(__DIR__), '_root');
    Dispatch::bind(new Dispatch($root))->addAlias('root', Dispatch::RESOURCES_DIR);

    $path = 'css/test.css';
    $hash = substr(md5_file(Path::system($root, Dispatch::RESOURCES_DIR, $path)), 0, 8);

    $this->assertEquals(
      Path::url(ResourceManager::MAP_RESOURCES, $hash, $path),
      ResourceManager::resources()->getResourceUri($path)
    );
    $this->assertEquals(
      Path::url(ResourceManager::MAP_RESOURCES, $hash, $path),
      ResourceManager::resources()->getResourceUri($path)
    );

    $this->assertEquals(
      Path::url(ResourceManager::MAP_ALIAS, 'root', $hash, $path),
      ResourceManager::alias('root')->getResourceUri($path)
    );

    $url = 'http://www.google.com/test.css';
    $this->assertEquals($url, ResourceManager::resources()->getResourceUri($url));

    $path = 'README.md';
    $hash = substr(md5_file(Path::system($root, 'vendor', 'packaged', 'dispatch', $path)), 0, 8);
    $this->assertEquals(
      Path::system(ResourceManager::MAP_VENDOR, 'packaged', 'dispatch', $hash, $path),
      ResourceManager::vendor('packaged', 'dispatch')->getResourceUri('README.md')
    );

    $this->expectExceptionMessage("invalid map type");
    (new ResourceManager('invalid'))->getResourceUri($path);
  }
}
