<?php

/**
 * @license MIT
 * @copyright (c) 2024 Virus3D
 */

declare(strict_types=1);

namespace PhpObfuscator;

use DirectoryIterator;
use Exception;

use function in_array;

use const DIRECTORY_SEPARATOR;

final class Obfuscator implements ObfuscatorInterface
{
    private Core $core;

    private bool $s_recursive = true;

    public function __construct()
    {
        $this->core = new Core();
    }//end __construct()

    public function setRecursive(bool $s_recursive): void
    {
        $this->s_recursive = $s_recursive;
    }//end setRecursive()

    public function obfuscateFile(string $path, string $target): void
    {
        $this->validateFile($path);
        $this->processFile($path, $target);
    }//end obfuscateFile()

    private function validateFile(string $path): void
    {
        if (! is_readable($path))
        {
            throw new Exception('File '.$path.' does not exist or is not readable.');
        }
    }//end validateFile()

    /**
     * Obfuscate and save file in new path.
     *
     * @throws Exception
     */
    public function process(string $sourceFile): string
    {
        return $this->core->obfuscateFile($sourceFile);
    }//end process()

    /**
     * Obfuscate and save file in new path.
     *
     * @throws Exception
     */
    private function processFile(string $sourceFile, string $targetFile): void
    {
        $obfuscatedSource = $this->core->obfuscateFile($sourceFile);

        file_put_contents($targetFile, $obfuscatedSource);
    }//end processFile()

    public function obfuscateDirectory(string $path, string $target): void
    {
        $this->validateDirectory($path);
        $this->processDirectory($path, $target);
    }//end obfuscateDirectory()

    private function validateDirectory(string $path): void
    {
        if (! file_exists($path))
        {
            throw new Exception('Directory '.$path.' does not exist or is not readable.');
        }
    }//end validateDirectory()

    private function createDirectory(string $directory): void
    {
        if (is_readable($directory))
        {
            return;
        }

        mkdir($directory, 0o755, true);
    }//end createDirectory()

    private function processDirectory(string $directory, string $target): void
    {
        $this->createDirectory($target);

        foreach (new DirectoryIterator($directory) as $fileInfo)
        {
            if ($fileInfo->isDot())
            {
                continue;
            }

            if ($fileInfo->isDir())
            {
                $newDir = $target.$fileInfo->getBasename().DIRECTORY_SEPARATOR;
                if ($this->s_recursive)
                {
                    $this->processDirectory($fileInfo->getPathname(), $newDir);
                }

                continue;
            }

            if ($fileInfo->isFile())
            {
                $fileName = $fileInfo->getFilename();
                $mimeType = mime_content_type($directory.DIRECTORY_SEPARATOR.$fileName);

                if (! in_array($mimeType, Config::getAllowedMimeTypes()))
                {
                    throw new Exception('Unsupported file type: '.$mimeType);
                }

                $this->processFile($directory.DIRECTORY_SEPARATOR.$fileName, $target.$fileName);
            }
        }//end foreach
    }//end processDirectory()
}//end class
