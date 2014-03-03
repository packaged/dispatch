<?php
namespace Packaged\Dispatch\Assets;

class PdfAsset extends AbstractAsset
{
  public function getExtension()
  {
    return 'pdf';
  }

  public function getContentType()
  {
    return "application/pdf";
  }
}
