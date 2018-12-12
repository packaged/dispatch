<?php
namespace Packaged\Dispatch\Resources\Font;

class PfbResource extends AbstractFontResource
{
  public function getExtension()
  {
    return 'pfb';
  }

  public function getContentType()
  {
    return "application/x-font-pfb";
  }
}
