<?php

namespace Claroline\AppBundle\Manager\File;

use Claroline\AppBundle\API\Utils\FileBag;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Helpers to manage zip archive in the application.
 * Mostly used in the import/export features.
 */
class ArchiveManager
{
    /** @var TempFileManager */
    private $tempFileManager;

    public function __construct(TempFileManager $tempFileManager)
    {
        $this->tempFileManager = $tempFileManager;
    }

    /**
     * Creates a new archive and fills it with the content of the FileBag if any.
     * If no destinationPath is specified, the archive will be created in the platform temp dir.
     */
    public function create(?string $destinationPath = null, ?FileBag $fileBag = null): ZipArchive
    {
        $archive = new ZipArchive();

        if (empty($destinationPath)) {
            $destinationPath = $this->tempFileManager->generate();
        }

        $archive->open($destinationPath, ZipArchive::CREATE);

        if ($fileBag) {
            foreach ($fileBag->all() as $archPath => $realPath) {
                $archive->addFile($realPath, $archPath);
            }
        }

        return $archive;
    }

    /**
     * Extracts files from an archive.
     * If no destinationPath is specified, the files will be extracted in the platform temp dir.
     */
    public function extractFiles(ZipArchive $archive, ?string $destinationPath = null): FileBag
    {
        $fileBag = new FileBag();

        if (empty($destinationPath)) {
            $destinationPath = $this->tempFileManager->generate();
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
        }

        $archive->extractTo($destinationPath);

        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS;
        $dirIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destinationPath, $flags));
        foreach ($dirIterator as $fileInfo) {
            $location = $fileInfo->getPathname();
            $fileName = substr($location, strlen($destinationPath) + 1);

            $fileBag->add($fileName, $location);
        }

        return $fileBag;
    }
}
