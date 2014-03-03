<?php
namespace Packaged\Dispatch\Assets\Video;

class QuicktimeAsset extends AbstractVideoAsset
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
