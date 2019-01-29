<?php
namespace Packaged\Dispatch\Tests\TestComponents;

use Packaged\Dispatch\Component\FixedClassComponent;
use Packaged\Dispatch\Component\UiComponent;
use Packaged\Dispatch\ResourceManager;
use Packaged\Helpers\Path;

abstract class AbstractComponent extends UiComponent implements FixedClassComponent
{
  public function getResourceDirectory()
  {
    return Path::system(__DIR__, 'DemoComponent');
  }

  protected function _requireResources(ResourceManager $manager)
  {
    $manager->requireCss('style.css');
  }

  public function getComponentClass(): string
  {
    return self::class;
  }

}
