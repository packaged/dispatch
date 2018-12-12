<?php
namespace Packaged\Dispatch\Resources\Video;

class FlvResource extends AbstractVideoResource
{
  public function getExtension()
  {
    return 'flv';
  }

  public function getContentType()
  {
    return "video/x-flv";
  }
}
