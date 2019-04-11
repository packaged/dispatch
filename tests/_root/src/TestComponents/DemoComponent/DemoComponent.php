<?php
namespace Packaged\Dispatch\Tests\TestComponents\DemoComponent;

use Packaged\Dispatch\Component\UiComponent;
use Packaged\Dispatch\ResourceManager;

class DemoComponent extends UiComponent
{
  public function getContentFile($allowBubbling = true)
  {
    return ResourceManager::component($this)->getResourceUri('content.txt', $allowBubbling);
  }

  public function getParentFile($allowBubbling = true)
  {
    return ResourceManager::component($this)->getResourceUri('parent.txt', $allowBubbling);
  }

  public function getOverrideFile($allowBubbling = true)
  {
    return ResourceManager::componentClass(self::class)->getResourceUri('override.txt', $allowBubbling);
  }
}
