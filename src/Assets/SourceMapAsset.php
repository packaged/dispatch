<?php
namespace Packaged\Dispatch\Assets;

class SourceMapAsset extends AbstractAsset
{
  public function getExtension()
  {
    return 'map';
  }

  public function getContentType()
  {
    return "application/json";
  }
}
