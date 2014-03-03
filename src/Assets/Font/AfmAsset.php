<?php
namespace Packaged\Dispatch\Assets\Font;

class AfmAsset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'afm';
  }

  public function getContentType()
  {
    return "application/x-font-afm";
  }
}
