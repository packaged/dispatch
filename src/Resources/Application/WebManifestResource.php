<?php
namespace Packaged\Dispatch\Resources\Application;

use Packaged\Dispatch\Resources\AbstractDispatchableResource;

class WebManifestResource extends AbstractDispatchableResource
{
  public function getExtension()
  {
    return 'webmanifest';
  }

  public function getContentType()
  {
    return "application/manifest+json";
  }
}
