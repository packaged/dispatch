<?php
namespace Packaged\Dispatch\Assets;

class JsonAsset extends AbstractAsset
{
  public function getExtension()
  {
    return 'json';
  }

  public function getContentType()
  {
    return "application/json";
  }
}
