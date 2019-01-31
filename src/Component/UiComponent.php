<?php
namespace Packaged\Dispatch\Component;

abstract class UiComponent implements DispatchableComponent
{
  use UiComponentTrait;

  public function __construct()
  {
    $this->_initDispatchableComponent($this);
  }
}
