<?php
namespace Packaged\Dispatch\Tests\TestComponents\DemoComponent\Child;

use Packaged\Dispatch\Component\FixedClassComponent;
use Packaged\Dispatch\Tests\TestComponents\DemoComponent\DemoComponent;

class FixedChildDemoComponent extends DemoComponent implements FixedClassComponent
{
  public function getComponentClass(): string
  {
    return self::class;
  }

}
