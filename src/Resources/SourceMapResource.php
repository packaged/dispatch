<?php
namespace Packaged\Dispatch\Resources;

class SourceMapResource extends AbstractResource
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
