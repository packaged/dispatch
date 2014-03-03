<?php
namespace Packaged\Dispatch\Assets\Font;

class PfaAsset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'pfa';
  }

  public function getContentType()
  {
    return "application/x-font-pfa";
  }
}
