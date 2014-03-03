<?php
namespace Packaged\Dispatch;

use Packaged\Dispatch\Assets\AbstractAsset;
use Packaged\Dispatch\Assets\IAsset;
use Symfony\Component\HttpFoundation\Response;

class AssetResponse
{
  protected static $assetMap = [
    'js'    => 'Javascript',
    'css'   => 'Css',
    'swf'   => 'Flash',
    'pdf'   => 'Pdf',
    'zip'   => 'Zip',
    'gif'   => 'Image\Gif',
    'ico'   => 'Image\Icon',
    'jpeg'  => 'Image\Jpeg',
    'jpg'   => 'Image\Jpg',
    'png'   => 'Image\Png',
    'svg'   => 'Image\Svg',
    'flv'   => 'Video\Flv',
    'mp4'   => 'Video\Mp4',
    'mpeg'  => 'Video\Mpeg',
    'mov'   => 'Video\Quicktime',
    'afm'   => 'Font\Afm',
    'dfont' => 'Font\Dfont',
    'eot'   => 'Font\eot',
    'otf'   => 'Font\OpenType',
    'pfa'   => 'Font\Pfa',
    'pfb'   => 'Font\Pfb',
    'pfm'   => 'Font\Pfm',
    'ttc'   => 'Font\Ttc',
    'ttf'   => 'Font\Ttf',
    'woff'  => 'Font\Woff',
  ];

  /**
   * @param $extension
   *
   * @return AbstractAsset
   */
  public function assetByExtension($extension)
  {
    if(isset(static::$assetMap[$extension]))
    {
      $class = '\Packaged\Dispatch\Assets\\';
      $class .= static::$assetMap[$extension] . 'Asset';
      return new $class;
    }
    return null;
  }

  public function createResponse(IAsset $asset)
  {
    $response = new Response($asset->getContent(), 200);
    $response->headers->set('Content-Type', $asset->getContentType());
    return $response;
  }
}
