<?php
namespace Packaged\Dispatch\Assets\Video;

class Mp4Asset extends AbstractVideoAsset
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
