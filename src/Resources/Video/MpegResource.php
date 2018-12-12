<?php
namespace Packaged\Dispatch\Resources\Video;

class MpegResource extends AbstractVideoResource
{
  public function getExtension()
  {
    return 'mpeg';
  }

  public function getContentType()
  {
    return "video/mpeg";
  }
}
