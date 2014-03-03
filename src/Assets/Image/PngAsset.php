<?php
namespace Packaged\Dispatch\Assets\Image;

class PngAsset extends AbstractImageAsset
{
  public function getExtension()
  {
    return 'png';
  }

  public function getContentType()
  {
    return "image/png";
  }
}
