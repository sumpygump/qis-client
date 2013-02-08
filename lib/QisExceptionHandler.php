<?php
/**
 * Qis Exception Handler class file 
 *
 * @package Qis
 */

/**
 * Qis Exception Handler
 * 
 * @package Qis
 * @author Jansen Price <jansen.price@nerdery.com>
 * @version $Id$
 */
class QisExceptionHandler
{
    /**
     * Qis object
     *
     * @var Qis
     */
    public static $qis = null;

    /**
     * Init the error handlers
     *
     * @param Qis $qis Qis object
     * @return void
     */
    public static function initHandlers($qis)
    {
        self::setQis($qis);

        set_exception_handler(array('QisExceptionHandler', 'handle'));
        set_error_handler(array('QisExceptionHandler', 'handle_error'));
    }

    /**
     * Restore the previously enabled exception and error handlers
     *
     * @return void
     */
    public static function restoreHandlers()
    {
        restore_exception_handler();
        restore_error_handler();
    }

    /**
     * Set Qis object
     * 
     * @param Qis $qis Qis object
     * @return void
     */
    public static function setQis($qis)
    {
        self::$qis = $qis;
    }

    /**
     * Handle an error
     *
     * @return void
     */
    public static function handle_error()
    {
        $args = func_get_args();
        if (count($args) < 4) {
            // If we didn't call this method properly, just silently fail
            return false;
        }

        list($errno, $message, $file, $line) = $args;

        $message = self::_error_code($errno)
            . ": " . $message . " in " . $file . ":" . $line;

        echo $message . "\n";
    }

    /**
     * Handle exception
     * 
     * @param Exception $e Exception object
     * @return void
     */
    public static function handle(Exception $e)
    {
        self::$qis->displayError($e->getMessage());
    }

    /**
     * Convert an error code into the PHP error constant name
     *
     * @param int $code The PHP error code
     * @return string
     */
    protected static function _error_code($code)
    {
        $error_levels = array(
            1     => 'E_ERROR',
            2     => 'E_WARNING',
            4     => 'E_PARSE',
            8     => 'E_NOTICE',
            16    => 'E_CORE_ERROR',
            32    => 'E_CORE_WARNING',
            64    => 'E_COMPILE_ERROR',
            128   => 'E_COMPILE_WARNING',
            256   => 'E_USER_ERROR',
            512   => 'E_USER_WARNING',
            1024  => 'E_USER_NOTICE',
            2048  => 'E_STRICT',
            4096  => 'E_RECOVERABLE_ERROR',
            8192  => 'E_DEPRECATED',
            16384 => 'E_USER_DEPRECATED',
        );

        if (!isset($error_levels[$code])) {
            return '';
        }

        return $error_levels[$code];
    }
}
