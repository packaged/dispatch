<?php
namespace Packaged\Dispatch\Assets\Font;

class OpenTypeAsset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'otf';
  }

  public function getContentType()
  {
    return "application/x-font-opentype";
  }
}
