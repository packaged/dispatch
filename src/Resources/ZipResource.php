<?php
namespace Packaged\Dispatch\Resources;

class ZipResource extends AbstractResource
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
