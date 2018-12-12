<?php
namespace Packaged\Dispatch\Resources\Font;

class AfmResource extends AbstractFontResource
{
  public function getExtension()
  {
    return 'afm';
  }

  public function getContentType()
  {
    return "application/x-font-afm";
  }
}
