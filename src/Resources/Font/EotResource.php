<?php
namespace Packaged\Dispatch\Resources\Font;

class EotResource extends AbstractFontResource
{
  public function getExtension()
  {
    return 'eot';
  }

  public function getContentType()
  {
    return "application/vnd.ms-fontobject";
  }
}
