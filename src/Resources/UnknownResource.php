<?php
namespace Packaged\Dispatch\Resources;

class UnknownResource extends AbstractDispatchableResource
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
