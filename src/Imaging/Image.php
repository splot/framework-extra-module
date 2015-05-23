<?php
namespace Splot\FrameworkExtraModule\Imaging;

use Gregwar\Image\Image as BaseImage;

class Image extends BaseImage
{

    /**
     * Creates an instance, usefull for one-line chaining
     */
    public static function open($file = '') {
        return new static($file);
    }

    /**
     * Creates an instance of a new resource
     */
    public static function create($width, $height) {
        return new static(null, $width, $height);
    }

    /**
     * Creates an instance of image from its data
     */
    public static function fromData($data) {
        $image = new static();
        $image->setData($data);
        return $image;
    }

    /**
     * Creates an instance of image from resource
     */
    public static function fromResource($resource) {
        $image = new static();
        $image->setResource($resource);
        return $image;
    }

}