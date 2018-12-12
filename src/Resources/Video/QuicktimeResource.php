<?php
namespace Packaged\Dispatch\Resources\Video;

class QuicktimeResource extends AbstractVideoResource
{
  public function getExtension()
  {
    return 'mov';
  }

  public function getContentType()
  {
    return "video/quicktime";
  }
}
