<?php
namespace Packaged\Dispatch\Resources\Font;

class Woff2Resource extends AbstractFontResource
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
