<?php
namespace Packaged\Dispatch\Assets\Video;

class MpegAsset extends AbstractVideoAsset
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
