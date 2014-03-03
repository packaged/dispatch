<?php
namespace Packaged\Dispatch\Assets\Font;

class TtcAsset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'ttc';
  }

  public function getContentType()
  {
    return "application/x-font-ttc";
  }
}
