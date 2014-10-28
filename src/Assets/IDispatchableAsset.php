<?php
namespace Packaged\Dispatch\Assets;

use Packaged\Dispatch\AssetManager;

interface IDispatchableAsset extends IAsset
{
  /**
   * Set the asset manager to process sub dispatchables through
   *
   * @param AssetManager $am
   *
   * @return IDispatchableAsset
   */
  public function setAssetManager(AssetManager $am);

  /**
   * Set the current working directory for an asset
   *
   * @param $directory
   *
   * @return IDispatchableAsset
   */
  public function setWorkingDirectory($directory);
}
