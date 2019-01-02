<?php
namespace Packaged\Dispatch\Tests\TestComponents\DemoComponent;

use Packaged\Dispatch\Component\DispatchableComponent;

class DemoComponent implements DispatchableComponent
{
  public function getResourceDirectory()
  {
    return __DIR__;
  }

}
