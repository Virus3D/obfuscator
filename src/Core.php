<?php

/**
 * @license MIT
 * @copyright (c) 2024 Virus3D
 */

declare(strict_types=1);

namespace PhpObfuscator;

use Exception;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\{Error, Parser, ParserFactory};

use const PHP_EOL;

final class Core
{
    private string $currentFileShebang = '';

    private Parser $parser;

    private Standard $prettyPrinter;

    private Collecting $errorHandler;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();

        $this->errorHandler = new Collecting();

        $this->prettyPrinter = new Standard();
    }//end __construct()

    private function isConsoleScript(string $content): bool
    {
        return '' !== $content && str_starts_with($content, '#!');
    }//end isConsoleScript()

    public function obfuscateFile(string $fileName): string
    {
        try
        {
            $content = file_get_contents($fileName);
        }
        catch (Exception $e)
        {
            throw new Exception('Cannot read file '.$fileName);
        }

        $tmpFilename     = '';
        $isConsoleScript = $this->isConsoleScript($content);

        /*
         * there is the thing, php_strip_whitespace() read only from file
         * so we need to craft file without shebang
         */
        if ($isConsoleScript)
        {
            $source = file($fileName);
			
            $this->currentFileShebang = array_shift($source);

            $tmpFilename = tempnam(sys_get_temp_dir(), 'php-obfuscator');
            file_put_contents($tmpFilename, implode(PHP_EOL, $source));
            $fileName = $tmpFilename;
        }

        try
        {
            $content = php_strip_whitespace($fileName);
			
            $obfuscatedSource = $this->obfuscate($content);
        }
        catch (Exception $e)
        {
            throw new Exception('Cannot process file '.$fileName.': '.$e->getMessage());
        }

        $obfuscatedSource = $this->minifyPhp($obfuscatedSource);

        if ($isConsoleScript)
        {
            $obfuscatedSource = $this->currentFileShebang.PHP_EOL.$obfuscatedSource;
            unlink($tmpFilename);
        }

        return $obfuscatedSource;
    }//end obfuscateFile()

    public function minifyPhp(string $code): string
    {
        $tmpFilename = tempnam(sys_get_temp_dir(), 'php-obfuscator-');
        file_put_contents($tmpFilename, $code);
        $result = php_strip_whitespace($tmpFilename);
        unlink($tmpFilename);

        return $result;
    }//end minifyPhp()

    /**
     * @throws Exception
     */
    private function obfuscate(string $fileContent): string
    {
        try
        {
            $parsed = $this->parser->parse($fileContent, $this->errorHandler);
        }
        catch (Error $error)
        {
            throw new Exception("Parse error: {$error->getFile()} {$error->getMessage()}");
        }
        catch (Exception $error)
        {
            throw new Exception('Cannot parse file: '.$error->getMessage());
        }

        if ($this->errorHandler->hasErrors())
        {
            $errorMessages = '';
            foreach ($this->errorHandler->getErrors() as $error)
            {
                $errorMessages .= "Error: {$error->getMessage()}\n";
            }

            throw new Exception('Parser encountered errors: '.$errorMessages);
        }

        // $obfuscatedSource = $this->traverser->traverse($parsed);

        return trim($this->prettyPrinter->prettyPrintFile($parsed));
    }//end obfuscate()
}//end class
