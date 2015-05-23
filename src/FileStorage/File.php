<?php
namespace Splot\FrameworkExtraModule\FileStorage;

use Symfony\Component\HttpFoundation\File\File as BaseFile;

use Splot\FrameworkExtraModule\FileStorage\FileInterface;

class File extends BaseFile implements FileInterface
{

    protected $parentDir;

    protected $relativePath;

    public function getFileName() {
        return $this->getBasename();
    }

    public function getRelativePath() {
        if ($this->relativePath) {
            return $this->relativePath;
        }

        $this->relativePath = str_replace($this->parentDir, '', $this->getPathName());
        return $this->relativePath;
    }

    /*******************
     * PATH SETTERS
     *******************/
    public function setParentDir($parentDir) {
        $this->parentDir = $parentDir;
    }

}