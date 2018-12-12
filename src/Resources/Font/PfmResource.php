<?php
namespace Packaged\Dispatch\Resources\Font;

class PfmResource extends AbstractFontResource
{
  public function getExtension()
  {
    return 'pfm';
  }

  public function getContentType()
  {
    return "application/x-font-pfm";
  }
}
