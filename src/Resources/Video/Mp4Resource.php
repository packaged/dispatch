<?php
namespace Packaged\Dispatch\Resources\Video;

class Mp4Resource extends AbstractVideoResource
{
  public function getExtension()
  {
    return 'mp4';
  }

  public function getContentType()
  {
    return "video/mp4";
  }
}
