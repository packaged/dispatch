<?php
namespace Packaged\Dispatch\Component;

use Packaged\Dispatch\ResourceManager;

trait UiComponentTrait
{
  private static $_initComponents = [];

  protected function _initDispatchableComponent(DispatchableComponent $component)
  {
    if(!isset(self::$_initComponents[static::class]))
    {
      $this->_requireResources(ResourceManager::component($component));
      self::$_initComponents[static::class] = true;
    }
  }

  protected function _requireResources(ResourceManager $manager)
  {
  }
}
