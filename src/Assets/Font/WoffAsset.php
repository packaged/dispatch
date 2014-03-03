<?php
namespace Packaged\Dispatch\Assets\Font;

class WoffAsset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'woff';
  }

  public function getContentType()
  {
    return "application/x-font-woff";
  }
}
