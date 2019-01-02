<?php
namespace Packaged\Dispatch\Tests\TestComponents\DemoComponent;

use Packaged\Dispatch\Component\UiComponent;

class DemoComponent extends UiComponent
{
  public function getResourceDirectory()
  {
    return __DIR__;
  }

}
