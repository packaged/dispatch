<?php
namespace Packaged\Dispatch\Assets\Font;

class Woff2Asset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'woff2';
  }

  public function getContentType()
  {
    return "font/woff2";
  }
}
