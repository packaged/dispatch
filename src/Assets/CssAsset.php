<?php
namespace Packaged\Dispatch\Assets;

class CssAsset extends AbstractAsset
{
  public function getExtension()
  {
    return 'css';
  }

  public function getContentType()
  {
    return "text/css";
  }
}
