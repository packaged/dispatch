<?php
namespace Packaged\Dispatch\Assets;

class UnknownAsset extends AbstractDispatchableAsset
{
  public function getExtension()
  {
    return null;
  }

  public function getContentType()
  {
    return "application/octet-stream";
  }
}
