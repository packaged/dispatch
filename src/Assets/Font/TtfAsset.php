<?php
namespace Packaged\Dispatch\Assets\Font;

class TtfAsset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'ttf';
  }

  public function getContentType()
  {
    return "application/x-font-ttf";
  }
}
