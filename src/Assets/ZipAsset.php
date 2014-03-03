<?php
namespace Packaged\Dispatch\Assets;

class ZipAsset extends AbstractAsset
{
  public function getExtension()
  {
    return 'zip';
  }

  public function getContentType()
  {
    return "application/zip";
  }
}
