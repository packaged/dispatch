<?php
namespace Packaged\Dispatch\Component;

use Packaged\Dispatch\ResourceManager;

abstract class UiComponent implements DispatchableComponent
{
  private static $_initComponents = [];

  public function __construct()
  {
    if(!isset(self::$_initComponents[static::class]))
    {
      $this->_requireResources(ResourceManager::component($this));
      self::$_initComponents[static::class] = true;
    }
  }

  protected function _requireResources(ResourceManager $manager)
  {
  }
}
