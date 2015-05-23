<?php
namespace Splot\FrameworkExtraModule\FileStorage;

use MD\Foundation\Utils\StringUtils;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as SfFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException as SfFileException;

use Splot\FrameworkExtraModule\FileStorage\File;
use Splot\FrameworkExtraModule\FileStorage\Exceptions\FileException;

class SimpleStorage
{

    protected $filesystem;

    protected $parentDir;

    protected $dir;

    protected $path;

    public function __construct(Filesystem $filesystem, $parentDir, $dir) {
        $this->filesystem = $filesystem;

        $this->parentDir = rtrim($parentDir, '/') .'/';
        $this->dir = trim($dir, '/') .'/';
        $this->path = $this->parentDir . $this->dir;

        // make sure that the path exists and can be made
        $this->filesystem->mkdir($this->path, 0777);
    }

    /**
     * Handles an uploaded file by putting it in the $targetDir.
     * 
     * @param UploadedFile $uploadedFile File object that has been uploaded - usually taken from Request object.
     * @param string $targetDir [optional] Where to (relatively to the storage root dir) put the file?
     * @param array $allowed [optional] What files are allowed? If not matching then will throw exception.
     * @param int $maxFileSize [optional] What is the maximum allowed file size for this file?
     * @return File
     */
    public function handleUploadedFile(UploadedFile $uploadedFile, $targetDir = '/', array $allowed = array(), $maxFileSize = 0) {
        array_walk($allowed, function($ext) {
            return strtolower($ext);
        });
        
        $targetDir = trim($targetDir, '/');
        $targetDir = $this->path . $targetDir . (empty($targetDir) ? '' : '/');
        $filenameElements = explode('.', $uploadedFile->getClientOriginalName());
        $extension = array_pop($filenameElements);
        $extension = strtolower($extension);
        $filename = implode('.', $filenameElements);
        $targetName = StringUtils::fileNameFriendly($filename .'-'. StringUtils::random() .'.'. $extension);
        $targetPath = $targetDir . $targetName;

        // create unique file name
        while(file_exists($targetPath)) {
            $targetName = StringUtils::fileNameFriendly($filename .'-'. StringUtils::random() .'.'. $extension);
            $targetPath = $targetDir . $targetName;
        }

        // basic check for allowed type
        if (!empty($allowed) && !in_array($extension, $allowed)) {
            throw new FileException('The uploaded file is not of a valid type (allowed: '. implode(', ', $allowed) .').');
        }

        // basic check for max allowed size
        if ($maxFileSize && $uploadedFile->getSize() > $maxFileSize) {
            throw new FileException('The uploaded file is too big (max allowed size is '. StringUtils::bytesToString($maxFileSize) .').');
        }
        
        try {
            $movedFile = $uploadedFile->move(rtrim($targetDir, '/'), $targetName);
        } catch (SfFileException $e) {
            // if exception thrown then convert it to our exception
            throw new FileException($e->getMessage(), $e->getCode());
        }

        $file = $this->convertSfFileToStorageFile($movedFile);

        return $file;
    }

    public function fileFromRelativePath($relativePath) {
        return $this->fileFromPath($this->parentDir . trim($relativePath, '/'));
    }

    public function fileFromPath($path) {
        $file = new File($path);
        $file->setParentDir($this->parentDir);
        return $file;
    }

    public function move(File $file, $dest) {
        throw new \MD\Foundation\Exceptions\NotImplementedException();
    }

    public function delete(File $file) {
        throw new \MD\Foundation\Exceptions\NotImplementedException();
    }

    /**********************
     * HELPERS
     **********************/
    protected function convertSfFileToStorageFile(SfFile $file) {
        return $this->fileFromPath($file->getPathname());
    }

    /**********************
     * GETTERS
     **********************/
    public function getParentDir() {
        return $this->parentDir;
    }

    public function getDir() {
        return $this->dir;
    }

    public function getPath() {
        return $this->path;
    }

}