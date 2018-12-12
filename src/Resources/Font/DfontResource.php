<?php
namespace Packaged\Dispatch\Resources\Font;

class DfontResource extends AbstractFontResource
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
