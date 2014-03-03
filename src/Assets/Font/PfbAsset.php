<?php
namespace Packaged\Dispatch\Assets\Font;

class PfbAsset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'pfb';
  }

  public function getContentType()
  {
    return "application/x-font-pfb";
  }
}
