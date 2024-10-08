<?php

/**
 * @license MIT
 * @copyright (c) 2024 Virus3D
 */

declare(strict_types=1);

namespace PhpObfuscator;

interface ObfuscatorInterface
{
    public function __construct();

    public function obfuscateFile(string $path, string $target): void;

    public function obfuscateDirectory(string $path, string $target): void;
}//end interface
