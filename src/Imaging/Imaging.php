<?php
namespace Splot\FrameworkExtraModule\Imaging;

use Splot\FrameworkExtraModule\Imaging\Image;

class Imaging
{

    public function open($file) {
        $path = ($file instanceof File) ? $file->getRealPath() : $file;
        return Image::open($path);
    }

    public function create($width, $height) {
        return Image::create($width, $height);
    }

}