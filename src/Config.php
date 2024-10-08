<?php

/**
 * @license MIT
 * @copyright (c) 2024 Virus3D
 */

declare(strict_types=1);

namespace PhpObfuscator;

use ArrayAccess;
use Symfony\Component\Yaml\Yaml;

use function is_string;

use const E_USER_ERROR;
use const PATHINFO_EXTENSION;

/**
 * @template TKey of string
 * @template TValue of string|int
 */
final class Config implements ArrayAccess
{
    /** @var array<TKey, TValue> */
    private array $config = [];

    public function __construct(string $resource)
    {
        if ($this->supports($resource))
        {
            $this->config = Yaml::parse(file_get_contents($resource));
        }
    }//end __construct()

    public function supports(string $resource): bool
    {
        return is_string($resource) && 'yaml' === pathinfo(
            $resource,
            PATHINFO_EXTENSION
        );
    }//end supports()

    /**
     * Checks if the specified config value exists.
     *
     * @param TKey $key the configuration option's name
     *
     * @return bool whether the configuration option exists
     */
    public function offsetExists(mixed $key): bool
    {
        return isset($this->config[$key]);
    }//end offsetExists()

    /**
     * Retrieves a configuration value.
     *
     * @param TKey $key the configuration option's name
     *
     * @return TValue The configuration value
     */
    public function offsetGet(mixed $key): string|int
    {
        return $this->config[$key] ?? '';
    }//end offsetGet()

    /**
     * Temporarily overwrites the value of a configuration variable.
     *
     * The configuration change will not persist. It will be lost
     * after the request.
     *
     * @param TKey $key   the configuration option's name
     * @param TValue $value the temporary value
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }//end offsetSet()

    /**
     * Called when deleting a configuration value directly, triggers an error.
     *
     * @param TKey $key the configuration option's name
     */
    public function offsetUnset(mixed $key): void
    {
        trigger_error('Config values not to be deleted', E_USER_ERROR);
    }//end offsetUnset()
}//end class
