<?php
namespace Packaged\Dispatch\Assets\Image;

class IconAsset extends AbstractImageAsset
{
  public function getExtension()
  {
    return 'ico';
  }

  public function getContentType()
  {
    return "image/x-icon";
  }
}
