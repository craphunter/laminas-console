<?php

/**
 * @see       https://github.com/laminas/laminas-console for the canonical source repository
 * @copyright https://github.com/laminas/laminas-console/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-console/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Console;

/**
 * An static, utility class for interacting with Console environment.
 * Declared abstract to prevent from instantiating.
 *
 * @category   Laminas
 * @package    Laminas_Console
 */
abstract class Console
{
    /**
     * @var Adapter\AdapterInterface
     */
    protected static $instance;

    /**
     * Create and return Adapter\AdapterInterface instance.
     *
     * @param  null|string  $forceAdapter Optional adapter class name. Ccan be absolute namespace or class name
     *                                    relative to Laminas\Console\Adapter\. If not provided, a best matching
     *                                    adapter will be automatically selected.
     * @param  null|string  $forceCharset optional charset name can be absolute namespace or class name relative to
     *                                    Laminas\Console\Charset\. If not provided, charset will be detected
     *                                    automatically.
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     * @return Adapter\AdapterInterface
     */
    public static function getInstance($forceAdapter = null, $forceCharset = null)
    {
        if (static::$instance instanceof Adapter\AdapterInterface) {
            return static::$instance;
        }

        // Create instance

        if ($forceAdapter !== null) {
            // Use the supplied adapter class
            if (substr($forceAdapter, 0, 1) == '\\') {
                $className = $forceAdapter;
            } elseif (stristr($forceAdapter, '\\')) {
                $className = __NAMESPACE__ . '\\' . ltrim($forceAdapter, '\\');
            } else {
                $className = __NAMESPACE__ . '\\Adapter\\' . $forceAdapter;
            }

            if (!class_exists($className)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Cannot find Console adapter class "%s"',
                    $className
                ));
            }
        } else {
            // Try to detect best instance for console
            $className = static::detectBestAdapter();

            // Check if we were able to detect console adapter
            if (!$className) {
                throw new Exception\RuntimeException('Cannot create Console adapter - am I running in a console?');
            }
        }

        // Create adapter instance
        static::$instance = new $className();

        // Try to use the supplied charset class
        if ($forceCharset !== null) {
            if (substr($forceCharset, 0, 1) == '\\') {
                $className = $forceCharset;
            } elseif (stristr($forceAdapter, '\\')) {
                $className = __NAMESPACE__ . '\\' . ltrim($forceCharset, '\\');
            } else {
                $className = __NAMESPACE__ . '\\Charset\\' . $forceCharset;
            }

            if (!class_exists($className)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Cannot find Charset class "%s"',
                    $className
                ));
            }

            // Set adapter charset
            static::$instance->setCharset(new $className());
        }

        return static::$instance;
    }

    /**
     * Check if currently running under MS Windows
     *
     * @see http://stackoverflow.com/questions/738823/possible-values-for-php-os
     * @return bool
     */
    public static function isWindows()
    {
        return
            ( defined('PHP_OS') && ( substr_compare(PHP_OS,'win',0,3,true) === 0) ) ||
            (getenv('OS') != false && substr_compare(getenv('OS'),'windows',0,7,true))
        ;
    }

    /**
     * Check if running under MS Windows Ansicon
     *
     * @return bool
     */
    public static function isAnsicon()
    {
        return getenv('ANSICON') !== false;
    }

    /**
     * Check if running in a console environment (CLI)
     *
     * @return bool
     */
    public static function isConsole()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * Try to detect best matching adapter
     * @return string|null
     */
    public static function detectBestAdapter()
    {
        // Check if we are in a console environment
        if (!static::isConsole()) {
            return null;
        }

        // Check if we're on windows
        if (static::isWindows()) {
            if (static::isAnsicon()) {
                $className = __NAMESPACE__ . '\Adapter\WindowsAnsicon';
            } else {
                $className = __NAMESPACE__ . '\Adapter\Windows';
            }

            return $className;
        }

        // Default is a Posix console
        $className = __NAMESPACE__ . '\Adapter\Posix';
        return $className;
    }

    /**
     * Pass-thru static call to current AdapterInterface instance.
     *
     * @param $funcName
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($funcName, $arguments)
    {
        $instance = static::getInstance();
        return call_user_func_array(array($instance, $funcName), $arguments);
    }
}
