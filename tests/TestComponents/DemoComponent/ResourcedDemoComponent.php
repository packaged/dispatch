<?php
namespace Packaged\Dispatch\Tests\TestComponents\DemoComponent;

use Packaged\Dispatch\Component\UiComponent;
use Packaged\Dispatch\ResourceManager;
use Packaged\Helpers\Path;

class ResourcedDemoComponent extends UiComponent
{
  public function getResourceDirectory()
  {
    return Path::system(__DIR__, 'resources');
  }

  protected function _requireResources(ResourceManager $manager)
  {
    $manager->requireCss('style.css');
  }
}
