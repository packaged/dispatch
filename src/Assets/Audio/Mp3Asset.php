<?php
namespace Packaged\Dispatch\Assets\Audio;

class Mp3Asset extends AbstractAudioAsset
{
    public function getExtension()
    {
        return 'mp3';
    }

    public function getContentType()
    {
        return "audio/mp3";
    }
}