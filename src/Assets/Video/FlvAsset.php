<?php
namespace Packaged\Dispatch\Assets\Video;

class FlvAsset extends AbstractVideoAsset
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
