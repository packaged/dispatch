<?php
namespace Packaged\Dispatch\Assets\Font;

class PfmAsset extends AbstractFontAsset
{
  public function getExtension()
  {
    return 'pmf';
  }

  public function getContentType()
  {
    return "application/x-font-pmf";
  }
}
