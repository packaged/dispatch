<?php
namespace Packaged\Dispatch;

use Packaged\Dispatch\Assets\AbstractAsset;
use Packaged\Dispatch\Assets\IAsset;
use Packaged\Dispatch\Assets\UnknownAsset;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetResponse
{
  public static $assetMap = [
    'js'    => 'Javascript',
    'json'  => 'Json',
    'css'   => 'Css',
    'scss'  => 'Scss',
    'less'  => 'Less',
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
    'webm'  => 'Video\Webm',
    'afm'   => 'Font\Afm',
    'dfont' => 'Font\Dfont',
    'eot'   => 'Font\Eot',
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
    $extension = strtolower($extension);
    if(isset(static::$assetMap[$extension]))
    {
      $class = '\Packaged\Dispatch\Assets\\';
      $class .= static::$assetMap[$extension] . 'Asset';
      return new $class;
    }
    return new UnknownAsset();
  }

  public function createResponse(IAsset $asset, Request $request)
  {
    $response = new Response();

    //Set the correct content type based on the asset
    $response->headers->set('Content-Type', $asset->getContentType());

    //Ensure the cache varies on the language and encoding
    //Domain specific content will vary on the uri itself
    $response->headers->set("Vary", "Accept-Encoding,Accept-Language");

    $content = $asset->getContent();

    //Set the etag to the hash of the request uri, as it is in itself a hash
    $response->setEtag(md5($content));
    $response->setPublic();

    //This resource should last for 30 days in cache
    $response->setMaxAge(2592000);
    $response->setSharedMaxAge(2592000);
    $response->setExpires((new \DateTime())->add(new \DateInterval('P30D')));

    //Set the last modified date to now
    $date = new \DateTime();
    $date->setTimezone(new \DateTimeZone('UTC'));
    $response->headers->set(
      'Last-Modified',
      $date->format('D, d M Y H:i:s') . ' GMT'
    );

    //Check to see if the client already has the content
    if($request->server->has('HTTP_IF_MODIFIED_SINCE'))
    {
      $response->setNotModified();
    }
    else
    {
      $response->setContent($content);
    }

    return $response;
  }
}
