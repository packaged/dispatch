<?php
namespace Packaged\Dispatch\Resources;

class PdfResource extends AbstractResource
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
