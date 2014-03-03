<?php
namespace Packaged\Dispatch\Assets\Font;

class EotAsset extends AbstractFontAsset
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
