<?php
namespace Packaged\Dispatch\Resources\Application;

use Packaged\Dispatch\Resources\AbstractResource;

class XmlResource extends AbstractResource
{
  public function getExtension()
  {
    return 'xml';
  }

  public function getContentType()
  {
    return "application/xml";
  }
}
