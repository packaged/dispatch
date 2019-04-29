<?php
namespace Packaged\Dispatch\Component;

use Packaged\Dispatch\ResourceManager;

trait UiComponentTrait
{
  private static $_initComponents = [];

  protected function _initDispatchableComponent(DispatchableComponent $component)
  {
    if(!isset(self::$_initComponents[$this->_getComponentClassName()]))
    {
      $this->_requireResources(ResourceManager::component($component));
      self::$_initComponents[$this->_getComponentClassName()] = true;
    }
  }

  protected function _getComponentClassName()
  {
    return static::class;
  }

  protected function _requireResources(ResourceManager $manager)
  {
  }
}
