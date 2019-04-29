<?php
namespace Packaged\Dispatch\Tests\TestComponents\DemoComponent\Child;

use Packaged\Dispatch\Tests\TestComponents\AbstractComponent;

class ChildComponent extends AbstractComponent
{
  public function __construct()
  {
    $this->_initDispatchableComponent();
    parent::__construct();
  }
}
