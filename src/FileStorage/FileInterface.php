<?php
namespace Splot\FrameworkExtraModule\FileStorage;

interface FileInterface
{

    public function getFilename();

    public function getExtension();

    public function getRealPath();

    public function getRelativePath();

}