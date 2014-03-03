<?php
namespace Packaged\Dispatch\Assets\Image;

class JpegAsset extends AbstractImageAsset
{
  public function getExtension()
  {
    return 'jpeg';
  }

  public function getContentType()
  {
    return "image/jpeg";
  }
}
