<?php
namespace Packaged\Dispatch\Resources\Font;

class TtfResource extends AbstractFontResource
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
