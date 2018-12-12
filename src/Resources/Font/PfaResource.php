<?php
namespace Packaged\Dispatch\Resources\Font;

class PfaResource extends AbstractFontResource
{
  public function getExtension()
  {
    return 'pfa';
  }

  public function getContentType()
  {
    return "application/x-font-pfa";
  }
}
