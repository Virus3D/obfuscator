<?php

/**
 * @license MIT
 * @copyright (c) 2024 Virus3D
 */

declare(strict_types=1);

namespace PhpObfuscator;

use Symfony\Component\Finder\{Finder, SplFileInfo};

use function chr;
use function ord;
use function sprintf;

use const E_USER_ERROR;
use const PHP_EOL;

/**
 * @template TKey of string
 * @template TValue of string|int
 */
final class Action
{
    private string $sourceDir;

    private string $targetDir;

    private string $tempDir;

    private string $codegzinflate;

    private string $codebase64decode;

    private string $codeRotEncode;

    /** @var string[] */
    private array $conf_list = [];

    /** @var array<string, string> */
    private array $conf_file = [];

    private Obfuscator $obfuscator;

    /**
     * @param Config<TKey, TValue> $config
     */
    public function __construct(private Config $config, private string $base_path, private bool $is_console)
    {
        $this->obfuscator = new Obfuscator();
        $this->sourceDir  = $this->base_path.$this->config['sourceDir'];
        $this->targetDir  = $this->base_path.$this->config['targetDir'];
        $this->tempDir    = $this->base_path.$this->config['tempDir'];

        $this->validateDirectories();
        $this->loadConfigFiles();

        $this->codegzinflate    = 'O'.$this->generateRandomString(12, '0O');
        $this->codebase64decode = 'O'.$this->generateRandomString(12, '0O');
        $this->codeRotEncode    = 'O'.$this->generateRandomString(12, '0O');
    }//end __construct()

    private function validateDirectories(): void
    {
        if (! file_exists($this->sourceDir))
        {
            trigger_error('Source Dir not found', E_USER_ERROR);
        }

        foreach ([$this->targetDir, $this->tempDir] as $dir)
        {
            if (! file_exists($dir) && ! mkdir($dir, 0o777, true))
            {
                trigger_error('Failed to create directory: '.$dir, E_USER_ERROR);
            }
        }
    }//end validateDirectories()

    private function loadConfigFiles(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->sourceDir)->name('config.cnf');

        foreach ($finder as $file)
        {
            $this->conf_list[] = $file->getRelativePath().'/';
        }
    }//end loadConfigFiles()

    private function generateRandomString(int $length, string $characters): string
    {
        $charactersLength = mb_strlen($characters);
        $randomString     = '';

        for ($i = 0; $i < $length; ++$i)
        {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }//end generateRandomString()

    public function run(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->sourceDir);
        foreach ($finder as $file)
        {
            $this->processFile($file);
        }
    }//end run()

    private function processFile(SplFileInfo $file): void
    {
        $path = $file->getRelativePathname();

        $extension = $file->getExtension();

        if ('cnf' === $extension)
        {
            return;
        }

        $this->ensureDirectoryExists($this->targetDir.$file->getRelativePath());
        $this->ensureDirectoryExists($this->tempDir.$file->getRelativePath());

        $sourcePath = $this->sourceDir.$path;
        $targetPath = $this->targetDir.$path;

        if ($this->isTargetFileFresh($file, $targetPath))
        {
            return;
        }

        if ('php' != $extension)
        {
            copy($sourcePath, $targetPath);
            $this->logTransfer($sourcePath, $file->getMTime());

            return;
        }

        $comment = $this->getComment($file->getRelativePath());

        $this->execute($path, $comment);
        $this->logTransfer($sourcePath, $file->getMTime());
    }//end processFile()

    private function ensureDirectoryExists(string $dir): void
    {
        if (! file_exists($dir))
        {
            mkdir($dir, 0o777, true);
        }
    }//end ensureDirectoryExists()

    private function isTargetFileFresh(SplFileInfo $file, string $targetPath): bool
    {
        return file_exists($targetPath) && $file->getMTime() <= stat($targetPath)['mtime'];
    }//end isTargetFileFresh()

    private function logTransfer(string $source, int $modTime): void
    {
        if ($this->is_console)
        {
            echo $source.' - '.date('Y-m-d H:i:s', $modTime)."\n";
        }
    }//end logTransfer()

    private function getComment(string $path): string
    {
        $path = trim($path, '/').'/';

        if (isset($this->conf_file[$path]))
        {
            return $this->conf_file[$path];
        }

        $conf = '';
        foreach ($this->conf_list as $item)
        {
            if (str_starts_with($path, $item))
            {
                $conf = $this->sourceDir.$item.'config.cnf';
            }
        }

        $this->conf_file[$path] = $conf;

        return $conf;
    }//end getComment()

    public function execute(string $filePath, string $comment): void
    {
        $content = $this->obfuscator->process($this->sourceDir.$filePath);
        file_put_contents($this->tempDir.$filePath.'.tmp', $content);
        $this->cleanFile($content);

        $encodeCount = (int) $this->config['cntEncode'];
        // Encode code
        for ($i = 0; $i < $encodeCount; ++$i)
        {
            $content = $this->encodeContent($content, '$'.$this->codegzinflate, '$'.$this->codebase64decode);
        }

        $content = $this->splitLines($content);
        $content = $this->addFunctions($content);
        $content = $this->addComment($content, $comment);
        $content = $this->addCodeTags($content);

        file_put_contents($this->targetDir.$filePath, $content);
    }//end execute()

    private function cleanFile(string &$content): void
    {
        $content = preg_replace(['/^\h*\v+/m', '/<\?php|<\?php\s+/'], ['', ''], $content);
    }//end cleanFile()

    private function encodeContent(string $content, string $gzAlias = 'gzinflate', string $base64alias = 'base64_decode'): string
    {
        return 'eval('.$gzAlias."({$base64alias}('".base64_encode(gzdeflate($content))."')));";
    }//end encodeContent()

    private function splitLines(string $content): string
    {
        return implode("\n", mb_str_split($content, 40));
    }//end splitLines()

    private function addFunctions(string $content): string
    {
        $keystroke1 = str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz_');
        $keystroke2 = str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz_');

        return sprintf(
            '$keystroke1=base64_decode("%s");'.PHP_EOL.
            '%s'.PHP_EOL.
            '%s'.PHP_EOL.
            '$keystroke2=$%s;'.PHP_EOL.
            '%s',
            base64_encode($keystroke1),
            $this->encodeFunction('rotencode'),
            $this->obfuscateKeystroke($keystroke1, 'rotencode', '$keystroke1', $this->codeRotEncode),
            $this->codeRotEncode.'("'.$this->rotEncode($keystroke2, 1).'", -1)',
            $this->obfuscateKeystroke($keystroke2, 'gzinflate', '$keystroke2', $this->codegzinflate).PHP_EOL.
            $this->obfuscateKeystroke($keystroke1, 'base64_decode', '$keystroke1', $this->codebase64decode).PHP_EOL.
            $content
        );
    }//end addFunctions()

    private function encodeFunction(string $functionName): string
    {
        return $this->encodeContent('if(!function_exists("'.$functionName.'")){function '.$functionName.'($string,$amount)  {$key=substr($string,0,1);return (strlen($string)===1) ? chr(ord($key)+$amount) : chr(ord($key)+$amount) . '.$functionName.'(substr($string,1),$amount);}}');
    }//end encodeFunction()

    private function obfuscateKeystroke(string $keystroke, string $function, string $keystrokeVar, string $code): string
    {
        return sprintf('$%s=%s;', $code, implode('.', array_map(static fn ($ch) => $keystrokeVar.'['.mb_strpos($keystroke, $ch).']', mb_str_split($function))));
    }//end obfuscateKeystroke()

    private function rotEncode(string $string, int $amount): string
    {
        return implode('', array_map(static fn ($char) => chr(ord($char) + $amount), mb_str_split($string)));
    }//end rotEncode()

    private function addComment(string $content, string $comment): string
    {
        if (empty($comment))
        {
            return $content;
        }

        $commentContent = file_get_contents($comment);

        return str_replace(
            ['{$year}', '{$date}'],
            [date('Y'), date('Y-m-d H:i:s (T)')],
            $commentContent
        ).$content;
    }//end addComment()

    private function addCodeTags(string $content): string
    {
        return "<?php\n".$content;
    }//end addCodeTags()
}//end class
