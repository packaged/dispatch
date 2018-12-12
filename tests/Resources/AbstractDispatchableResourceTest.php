<?php

namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\Manager\ResourceManager;
use Packaged\Dispatch\Resources\CssResource;
use Packaged\Helpers\Path;
use PHPUnit\Framework\TestCase;

class AbstractDispatchableResourceTest extends TestCase
{
  public function testProcessesContent()
  {
    $root = dirname(dirname(__DIR__));
    Dispatch::bind(new Dispatch($root))->addAlias('root', Path::system($root, Dispatch::RESOURCES_DIR));
    $manager = ResourceManager::resources();

    $resource = new CssResource();
    $resource->setManager($manager);
    $resource->setProcessingPath('css/test.css');
    $resource->setContent(file_get_contents(Path::system($root, Dispatch::RESOURCES_DIR, 'css', 'test.css')));
    $content = $resource->getContent();
    $this->assertContains('url(\'r/d41d8cd9/img/x.jpg\')', $content);
  }
}
