<?php
namespace Packaged\Dispatch\Component;

use Packaged\Dispatch\ResourceManager;

trait UiComponentTrait
{
  private static $_initComponents = [];

  protected function _initDispatchableComponent(DispatchableComponent $component = null)
  {
    if(!isset(self::$_initComponents[static::class]))
    {
      if($component !== null)
      {
        $this->_requireResources(ResourceManager::component($component));
      }
      else
      {
        $this->_requireResources(ResourceManager::componentClass(static::class));
      }
      self::$_initComponents[static::class] = true;
    }
  }

  protected function _requireResources(ResourceManager $manager)
  {
  }
}
