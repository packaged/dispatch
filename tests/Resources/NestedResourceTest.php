<?php
namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\ResourceManager;
use Packaged\Dispatch\Resources\CssResource;
use Packaged\Helpers\Path;
use PHPUnit\Framework\TestCase;

class NestedResourceTest extends TestCase
{
  public function testProcessesContent()
  {
    $root = Path::system(dirname(__DIR__), '_root');
    Dispatch::bind(new Dispatch($root, '/_r/'))->addAlias('root', Path::system($root, Dispatch::RESOURCES_DIR));
    $manager = ResourceManager::resources();

    $resource = new CssResource();
    $resource->setManager($manager);
    $resource->setProcessingPath('css/test.css');
    $resource->setContent(file_get_contents(Path::system($root, Dispatch::RESOURCES_DIR, 'css', 'test.css')));
    $content = $resource->getContent();
    $this->assertStringContainsString('url(/_r/r/395d1a0e8999/img/x.jpg?x=y&request=b)', $content);
  }
}
