<?php
namespace Packaged\Dispatch\Resources\Audio;

class Mp3Resource extends AbstractAudioResource
{
  public function getExtension()
  {
    return 'mp3';
  }

  public function getContentType()
  {
    return "audio/mpeg";
  }
}
