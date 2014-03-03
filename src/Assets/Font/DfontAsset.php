<?php
namespace Packaged\Dispatch\Assets\Font;

class DfontAsset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'dfont';
  }

  public function getContentType()
  {
    return "application/x-font-dfont";
  }
}
