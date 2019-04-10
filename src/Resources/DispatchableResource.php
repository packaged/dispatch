<?php
namespace Packaged\Dispatch\Resources;

use Packaged\Dispatch\ResourceManager;

interface DispatchableResource extends DispatchResource
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
