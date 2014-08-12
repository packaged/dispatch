<?php
namespace Packaged\Dispatch\Assets\Video;

class WebmAsset extends AbstractVideoAsset
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
