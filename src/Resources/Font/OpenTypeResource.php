<?php
namespace Packaged\Dispatch\Resources\Font;

class OpenTypeResource extends AbstractFontResource
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
