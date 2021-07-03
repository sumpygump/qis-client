<?php
/**
 * Qis Exception Handler test file
 *
 * @package Qis
 */

namespace Qis\Tests;

use BaseTestCase;
use Qis\ExceptionHandler;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;
use Exception;

/**
 * Mock Qis Exception Handler
 *
 * @uses QisExceptionHandler
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisExceptionHandler extends ExceptionHandler
{
    /**
     * Get Qis
     *
     * @return Qis
     */
    public static function getQis()
    {
        return self::$qis;
    }

    /**
     * Error code
     *
     * @param int $code Code
     * @return int
     */
    public static function errorCode($code)
    {
        return self::_error_code($code);
    }
}

/**
 * QisExceptionHandler Test class
 *
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class ExceptionHandlerTest extends BaseTestCase
{
    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown(): void
    {
        ExceptionHandler::restoreHandlers();
    }

    /**
     * Test initing handlers with no arguments
     *
     * @return void
     */
    public function testInitHandlersNoArguments()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage("Too few arguments");
        ExceptionHandler::initHandlers();
    }

    /**
     * Test init handlers normal
     *
     * @return void
     */
    public function testInitHandlersNormal()
    {
        ExceptionHandler::initHandlers($this->_getDefaultQisObject());

        // No side effects to confirm
        $this->assertTrue(true);
    }

    /**
     * Test set Qis
     *
     * @return void
     */
    public function testSetQis()
    {
        $fakeString = 'fake';
        MockQisExceptionHandler::setQis($fakeString);
        $fakeQis = MockQisExceptionHandler::getQis();
        $this->assertEquals($fakeString, $fakeQis);
    }

    /**
     * Test handle error
     *
     * @return void
     */
    public function testHandleError()
    {
        $errorNumber = E_ERROR;
        $message     = 'An error occurred';
        $filename    = 'filename.php';
        $line        = '1';

        $expected = "E_ERROR: An error occurred in filename.php:1\n";

        ob_start();
        ExceptionHandler::handle_error(
            $errorNumber, $message, $filename, $line
        );
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test handle error with no arguments
     *
     * @return void
     */
    public function testHandleErrorWithNoArguments()
    {
        ob_start();
        ExceptionHandler::handle_error();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('', $result);
    }

    /**
     * Test handle
     *
     * @return void
     */
    public function testHandle()
    {
        ExceptionHandler::initHandlers($this->_getDefaultQisObject());

        $exception = new Exception('There was a problem.', 1);

        ob_start();
        ExceptionHandler::handle($exception);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('There was a problem.', $result);
    }

    /**
     * Test error code
     *
     * @return void
     */
    public function testErrorCode()
    {
        $level = MockQisExceptionHandler::errorCode(1);

        $this->assertEquals('E_ERROR', $level);
    }

    /**
     * Test error code zero
     *
     * @return void
     */
    public function testErrorCodeZero()
    {
        $level = MockQisExceptionHandler::errorCode(0);

        $this->assertEquals('', $level);
    }

    /**
     * Get default qis object
     *
     * @return Qis
     */
    protected function _getDefaultQisObject()
    {
        $args     = new Qi_Console_ArgV(array());
        $terminal = new Qi_Console_Terminal();

        return new Qis($args, $terminal);
    }
}
