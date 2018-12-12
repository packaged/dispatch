<?php
namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Resources\JavascriptResource;
use PHPUnit\Framework\TestCase;

class JavascriptResourceTest extends TestCase
{
  public function testMinify()
  {
    $original = 'function myFunction(){
      alert("Hello\nHow are you?");
    }';

    $nominify = '@' . 'do-not-minify
    function myFunction(){
      alert("Hello\nHow are you?");
    }';

    $resource = new JavascriptResource();

    $resource->setContent($original);
    $this->assertEquals(
      'function myFunction(){alert("Hello\nHow are you?");}',
      $resource->getContent()
    );

    $resource->setContent($nominify);
    $this->assertEquals($nominify, $resource->getContent());

    $resource->setContent($original);
    $resource->setOptions(['minify' => 'false']);
    $this->assertEquals($original, $resource->getContent());
  }

  public function testResource()
  {
    $resource = new JavascriptResource();
    $this->assertEquals('js', $resource->getExtension());
    $this->assertEquals('text/javascript', $resource->getContentType());
  }

  /**
   * @ref  Issue 2
   * @link https://github.com/packaged/dispatch/issues/2
   */
  public function testSingleLineCommands()
  {
    $raw = '$(document).ready(function(){

    $(window).scroll(function () {

        var max_scroll = 273; //height to scroll before hitting nav-bar

        var navbar = $(".main-nav");
    });
});';

    $resource = new JavascriptResource();
    $resource->setContent($raw);
    $this->assertEquals(
      '$(document).ready(function(){$(window).scroll(function()'
      . '{var max_scroll=273;var navbar=$(".main-nav");});});',
      $resource->getContent()
    );
  }

  public function testMinifyException()
  {
    $raw = 'var string = "This string is hanging out.

    alert($x)';
    $resource = new JavascriptResource();
    $resource->setContent($raw);
    $resource->setOptions(['minify' => 'true']);
    $this->assertEquals($raw, $resource->getContent());
  }
}
