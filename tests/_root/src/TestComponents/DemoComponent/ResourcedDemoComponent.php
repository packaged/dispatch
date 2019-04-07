<?php
namespace Packaged\Dispatch\Tests\TestComponents\DemoComponent;

use Packaged\Dispatch\Component\UiComponent;
use Packaged\Dispatch\ResourceManager;

class ResourcedDemoComponent extends UiComponent
{
  protected function _requireResources(ResourceManager $manager)
  {
    $manager->requireCss('style.css');
  }
}
