<?php
namespace Packaged\Dispatch\Assets\Image;

class GifAsset extends AbstractImageAsset
{
  public function getExtension()
  {
    return 'gif';
  }

  public function getContentType()
  {
    return "image/gif";
  }
}
