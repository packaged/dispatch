<?php
namespace Packaged\Dispatch\Resources;

class JsonResource extends AbstractResource
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
