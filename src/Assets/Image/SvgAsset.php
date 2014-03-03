<?php
namespace Packaged\Dispatch\Assets\Image;

class SvgAsset extends AbstractImageAsset
{
  public function getExtension()
  {
    return 'svg';
  }

  public function getContentType()
  {
    return "image/svg+xml";
  }
}
