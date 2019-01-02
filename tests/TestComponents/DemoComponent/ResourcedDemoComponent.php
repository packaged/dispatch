<?php
namespace Packaged\Dispatch\Tests\TestComponents\DemoComponent;

use Packaged\Dispatch\Component\DispatchableComponent;
use Packaged\Helpers\Path;

class ResourcedDemoComponent implements DispatchableComponent
{
  public function getResourceDirectory()
  {
    return Path::system(__DIR__, 'resources');
  }

}
