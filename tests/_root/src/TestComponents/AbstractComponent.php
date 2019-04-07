<?php
namespace Packaged\Dispatch\Tests\TestComponents;

use Packaged\Dispatch\Component\FixedClassComponent;
use Packaged\Dispatch\Component\UiComponent;
use Packaged\Dispatch\ResourceManager;

abstract class AbstractComponent extends UiComponent implements FixedClassComponent
{
  protected function _requireResources(ResourceManager $manager)
  {
    $manager->requireCss('style.css');
  }

  public function getComponentClass(): string
  {
    return self::class;
  }

}
