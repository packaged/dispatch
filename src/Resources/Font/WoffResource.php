<?php
namespace Packaged\Dispatch\Resources\Font;

class WoffResource extends AbstractFontResource
{
  public function getExtension()
  {
    return 'woff';
  }

  public function getContentType()
  {
    return "application/x-font-woff";
  }
}
