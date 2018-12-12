<?php
namespace Packaged\Dispatch\Resources\Font;

class TtcResource extends AbstractFontResource
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
