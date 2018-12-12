<?php
namespace Packaged\Dispatch\Resources\Video;

class WebmResource extends AbstractVideoResource
{
  public function getExtension()
  {
    return 'webm';
  }

  public function getContentType()
  {
    return "video/webm";
  }
}
