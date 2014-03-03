<?php
namespace Packaged\Dispatch\Assets;

class JavascriptAsset extends AbstractAsset
{
  public function getExtension()
  {
    return 'js';
  }

  public function getContentType()
  {
    return "text/javascript";
  }
}
