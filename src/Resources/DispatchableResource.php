<?php
namespace Packaged\Dispatch\Resources;

use Packaged\Dispatch\Manager\ResourceManager;

interface DispatchableResource extends Resource
{
  /**
   * Set the asset manager to process sub dispatchables through
   *
   * @param ResourceManager $am
   *
   * @return DispatchableResource
   */
  public function setManager(ResourceManager $am);
}
