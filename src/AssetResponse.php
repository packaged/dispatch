<?php
namespace Packaged\Dispatch;

use Packaged\Dispatch\Assets\AbstractAsset;
use Packaged\Dispatch\Assets\IAsset;
use Packaged\Dispatch\Assets\UnknownAsset;
use Symfony\Component\HttpFoundation\Request;

class AssetResponse
{
  private static $_assetMap = [
    'js'    => '\Packaged\Dispatch\Assets\JavascriptAsset',
    'json'  => '\Packaged\Dispatch\Assets\JsonAsset',
    'css'   => '\Packaged\Dispatch\Assets\CssAsset',
    'swf'   => '\Packaged\Dispatch\Assets\FlashAsset',
    'pdf'   => '\Packaged\Dispatch\Assets\PdfAsset',
    'zip'   => '\Packaged\Dispatch\Assets\ZipAsset',
    'gif'   => '\Packaged\Dispatch\Assets\Image\GifAsset',
    'ico'   => '\Packaged\Dispatch\Assets\Image\IconAsset',
    'jpeg'  => '\Packaged\Dispatch\Assets\Image\JpegAsset',
    'jpg'   => '\Packaged\Dispatch\Assets\Image\JpgAsset',
    'png'   => '\Packaged\Dispatch\Assets\Image\PngAsset',
    'svg'   => '\Packaged\Dispatch\Assets\Image\SvgAsset',
    'flv'   => '\Packaged\Dispatch\Assets\Video\FlvAsset',
    'mp4'   => '\Packaged\Dispatch\Assets\Video\Mp4Asset',
    'mpeg'  => '\Packaged\Dispatch\Assets\Video\MpegAsset',
    'mov'   => '\Packaged\Dispatch\Assets\Video\QuicktimeAsset',
    'webm'  => '\Packaged\Dispatch\Assets\Video\WebmAsset',
    'afm'   => '\Packaged\Dispatch\Assets\Font\AfmAsset',
    'dfont' => '\Packaged\Dispatch\Assets\Font\DfontAsset',
    'eot'   => '\Packaged\Dispatch\Assets\Font\EotAsset',
    'otf'   => '\Packaged\Dispatch\Assets\Font\OpenTypeAsset',
    'pfa'   => '\Packaged\Dispatch\Assets\Font\PfaAsset',
    'pfb'   => '\Packaged\Dispatch\Assets\Font\PfbAsset',
    'pfm'   => '\Packaged\Dispatch\Assets\Font\PfmAsset',
    'ttc'   => '\Packaged\Dispatch\Assets\Font\TtcAsset',
    'ttf'   => '\Packaged\Dispatch\Assets\Font\TtfAsset',
    'woff'  => '\Packaged\Dispatch\Assets\Font\WoffAsset',
  ];

  public static function getExtensions()
  {
    return array_keys(self::$_assetMap);
  }

  public static function addAssetType($ext, $classname)
  {
    if(is_object($classname))
    {
      $classname = get_class($classname);
    }
    self::$_assetMap[$ext] = $classname;
  }

  /**
   * @param $extension
   *
   * @return AbstractAsset
   */
  public function assetByExtension($extension)
  {
    $extension = strtolower($extension);
    if(isset(self::$_assetMap[$extension]))
    {
      $class = self::$_assetMap[$extension];
      return new $class;
    }
    return new UnknownAsset();
  }

  public function createResponse(IAsset $asset, Request $request)
  {
    $response = new DispatchResponse();

    //Set the correct content type based on the asset
    $response->headers->set('Content-Type', $asset->getContentType());
    $response->headers->set('X-Content-Type-Options', 'nosniff');

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
