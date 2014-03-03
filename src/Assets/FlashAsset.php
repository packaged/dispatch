<?php
namespace Packaged\Dispatch\Assets;

class FlashAsset extends AbstractAsset
{
  public function getExtension()
  {
    return 'swf';
  }

  public function getContentType()
  {
    return "application/x-shockwave-flash";
  }
}
