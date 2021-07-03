<?php
/**
 * Qis Coverage module test
 *
 * @package Qis
 */

namespace Qis\Tests\Module;

use \BaseTestCase;
use Qis\Module\Coverage;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Mock Qis Module Coverage
 *
 * @uses Qis_Module_Coverage
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleCoverage extends Coverage
{
}

/**
 * CoverageTest
 *
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class CoverageTest extends BaseTestCase
{
    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        $path = realpath('.') . DIRECTORY_SEPARATOR . '.qis';
        mkdir($path);

        $this->_createObject();
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown(): void
    {
        $path = realpath('.') . DIRECTORY_SEPARATOR . '.qis';
        if (file_exists($path)) {
            passthru("rm -rf $path");
        }
    }

    /**
     * Test constructor with no arguments
     *
     * @return void
     */
    public function testConstructorWithNoArguments()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage("Too few arguments");
        $this->_object = new Coverage();
    }

    /**
     * Test get default ini
     *
     * @return void
     */
    public function testGetDefaultIni()
    {
        $defaultIni = Coverage::getDefaultIni();

        $this->assertStringContainsString(
            '; Module for code coverage of unit tests', $defaultIni
        );
        $this->assertStringContainsString('coverage.ignorePaths=', $defaultIni);
    }

    /**
     * testExecuteNoArgument
     *
     * @return void
     */
    public function testExecuteNoArgument()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage("Too few arguments");

        $this->_object->execute();
    }

    /**
     * testExecute
     *
     * @return void
     */
    public function testExecute()
    {
        $this->expectException(\Qis\Module\CoverageException::class);
        $this->expectExceptionMessage("Cannot find file");

        $args = array();
        $args = new Qi_Console_ArgV($args);

        $this->_object->execute($args);
    }

    /**
     * Test execute with argument
     *
     * @return void
     */
    public function testExecuteWithArgument()
    {
        $this->expectException(\Qis\Module\CoverageException::class);
        $this->expectExceptionMessage("Cannot find file");

        $args = array(
            'coverage',
            'foo',
            'wong.txt',
        );

        $args = new Qi_Console_ArgV($args);

        $this->_object->execute($args);
    }

    /**
     * Get get help message
     *
     * @return void
     */
    public function testGetHelpMessage()
    {
        $help = $this->_object->getHelpMessage();

        $this->assertStringContainsString('Show code coverage for unit tests.', $help);
    }

    /**
     * Test get extended help message
     *
     * @return void
     */
    public function testGetExtendedHelpMessage()
    {
        $help = $this->_object->getExtendedHelpMessage();

        $this->assertStringContainsString('Usage: coverage [OPTIONS] [filename]', $help);
        $this->assertStringContainsString('Valid Options:', $help);
    }

    /**
     * Test get summary
     *
     * @return void
     */
    public function testGetSummary()
    {
        $summary = $this->_object->getSummary();

        $this->assertStringContainsString('Coverage results:', $summary);
        $this->assertStringNotContainsString('Coverage: ', $summary);
    }

    /**
     * Test get short summary
     *
     * @return void
     */
    public function testGetShortSummary()
    {
        $summary = $this->_object->getSummary(true);

        $this->assertStringContainsString('Coverage: ', $summary);
        $this->assertStringNotContainsString('Coverage results:', $summary);
    }

    /**
     * Test get status
     *
     * @return void
     */
    public function testGetStatus()
    {
        $status = $this->_object->getStatus();

        $this->assertEquals(false, $status);
    }

    /**
     * Create object
     *
     * @param bool $initialize Whether to initialize
     * @param Qi_Console_ArgV $args Arguments
     * @return Coverage
     */
    protected function _createObject($initialize = true, $args = array())
    {
        $settings = array(
            'root'        => '.',
            'ignorePaths' => 'foo,bar',
        );

        $this->_object = new MockQisModuleCoverage(
            $this->_getDefaultQisObject($args), $settings
        );

        if ($initialize) {
            $this->_object->initialize();
        }
    }

    /**
     * Get default Qis object
     *
     * @param Qi_Console_ArgV $args Arguments
     * @return Qis
     */
    protected function _getDefaultQisObject($args = array())
    {
        $args     = new Qi_Console_ArgV($args);
        $terminal = new Qi_Console_Terminal();

        Qis::$exit = false;
        return new Qis($args, $terminal);
    }
}
