<?php

/**
 * Test codingstandard module
 *
 * @package Qis
 */

namespace Qis\Tests\Module;

use BaseTestCase;
use Qis\Module\Codingstandard;
use Qis\Module\CodingStandardException;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Mock Qis Module coding standard
 *
 * @uses Qis_Module_Codingstandard
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleCodingstandard extends Codingstandard
{
    /**
     * Get standard
     *
     * @return string
     */
    public function getStandard()
    {
        return $this->_standard;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Check version
     *
     * @return bool
     */
    public function checkVersion()
    {
        return $this->_checkVersion();
    }
}

/**
 * MockQisModuleCodingstandardErrorLevel
 *
 * @uses MockQisModuleCodingstandard
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleCodingstandardErrorLevel extends MockQisModuleCodingstandard
{
    /**
     * Get Project Summary
     *
     * @return array
     */
    public function getProjectSummary()
    {
        return array('error_level' => 4);
    }
}

/**
 * Codingstandard Module Test class
 *
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class CodingstandardTest extends BaseTestCase
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
        $this->_object = new Codingstandard();
    }

    /**
     * testConstructorWithoutSecondArgument
     *
     * @return void
     */
    public function testConstructorWithoutSecondArgument()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage("Too few arguments");
        $this->_object = new Codingstandard(
            $this->_getDefaultQisObject()
        );
    }

    /**
     * Test constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $settings = array();

        $this->_object = new Codingstandard(
            $this->_getDefaultQisObject(),
            $settings
        );

        $this->assertInstanceOf('Qis\Module\Codingstandard', $this->_object);
    }

    /**
     * Test constructor set defaults
     *
     * @return void
     */
    public function testConstructorSetDefaults()
    {
        $settings = array(
            'standard' => 'Foox',
            'path'     => 'vvvvv',
        );

        $this->_object = new MockQisModuleCodingstandard(
            $this->_getDefaultQisObject(),
            $settings
        );

        $this->assertEquals('Foox', $this->_object->getStandard());
        $this->assertEquals('vvvvv', $this->_object->getPath());
    }

    /**
     * Test initialize
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->_createObject(false);

        $this->_object->initialize();

        $path = realpath('.') . DIRECTORY_SEPARATOR . '.qis/codingstandard/';
        // This should have created files in the directory.
        // assert they exist
        $this->assertTrue(file_exists($path));
        $this->assertTrue(file_exists($path . 'cs.db3'));
        $this->assertTrue(file_exists($path . 'db.log'));
    }

    /**
     * testExecuteNoArguments
     *
     * @return void
     */
    public function testExecuteNoArguments()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage("Too few arguments");
        $this->_object->execute();
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $args = new Qi_Console_ArgV(array());

        ob_start();
        $this->_object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Running Codingstandard module', $result);
        $this->assertStringContainsString(
            'Sniffing code with \'PSR2\' standard...',
            $result
        );
        $this->assertStringContainsString('Writing results to db...', $result);
        $this->assertStringContainsString('Codingstandard results:', $result);
    }

    /**
     * testCheckVersion
     *
     * @return void
     */
    public function testCheckVersion()
    {
        $this->expectException(CodingStandardException::class);

        $this->_object->setOption('phpcsbin', 'ffffffff');
        $this->_object->checkVersion();
    }

    /**
     * Test check version cannot detect version
     *
     * @return void
     */
    public function testCheckVersionCannotDetectVersion()
    {
        // The : command will output nothing and return status 0
        // This means no version output will be found so checkVersion will
        // return false
        $this->_object->setOption('phpcsbin', ':');
        $result = $this->_object->checkVersion();

        $this->assertFalse($result);
    }

    /**
     * Test check version not found match
     *
     * @return void
     */
    public function testCheckVersionNotFoundMatch()
    {
        // The ls command doesn't output the version in the same format as
        // phpcs
        $this->_object->setOption('phpcsbin', 'ls');
        $this->expectException(\Qis\Module\CodingStandardException::class);
        $result = $this->_object->checkVersion();

        $this->assertFalse($result);
    }

    /**
     * Test execute with path not found
     *
     * @return void
     */
    public function testExecuteWithPathNotFound()
    {
        $args = array(
            'cs',
            'foo',
            'margarine',
        );
        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->_object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("Path `margarine' not found", $result);
    }

    /**
     * Test execute with valid path
     *
     * @return void
     */
    public function testExecuteWithValidPath()
    {
        $args = array(
            'cs',
            'foo',
            'src/Qis/Command/AllTest.php',
        );
        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->_object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("Sniffing code with", $result);
        $this->assertStringContainsString("Codingstandard results:", $result);
    }

    /**
     * Test execute with multiple paths
     *
     * @return void
     */
    public function testExecuteWithMultiplePaths()
    {
        $args = array(
            'cs',
            'foo',
            'grab,bag,hag',
        );
        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->_object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("Path `grab,bag,hag' not found", $result);
    }

    /**
     * Test execute with list command
     *
     * @return void
     */
    public function testExecuteWithListCommand()
    {
        $args = array(
            'cs',
            '--list',
        );

        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->_object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringNotContainsString("PHP CODE SNIFFER REPORT SUMMARY", $result);
        $this->assertStringContainsString("Running Codingstandard module task...", $result);
    }

    /**
     * Test get help message
     *
     * @return void
     */
    public function testGetHelpMessage()
    {
        $message = $this->_object->getHelpMessage();
        $this->assertStringContainsString('Run coding standard validation', $message);
    }

    /**
     * Test get extended help message
     *
     * @return void
     */
    public function testGetExtendedHelpMessage()
    {
        $message = $this->_object->getExtendedHelpMessage();
        $this->assertStringContainsString('Usage: cs', $message);
        $this->assertStringContainsString('Valid Options:', $message);
    }

    /**
     * Test get summary
     *
     * @return void
     */
    public function testGetSummary()
    {
        $summary = $this->_object->getSummary();

        $this->assertStringNotContainsString('Codingstandard error level', $summary);
    }

    /**
     * Test get summary short
     *
     * @return void
     */
    public function testGetSummaryShort()
    {
        $summary = $this->_object->getSummary(true);

        $this->assertStringContainsString('Codingstandard: No data.', $summary);
    }

    /**
     * Test get default ini
     *
     * @return void
     */
    public function testGetDefaultIni()
    {
        $defaultIni = $this->_object->getDefaultIni();

        $this->assertStringContainsString('; Module to run codesniffs', $defaultIni);
        $this->assertStringContainsString('codingstandard.standard=', $defaultIni);
    }

    /**
     * Test get status
     *
     * @return void
     */
    public function testGetStatus()
    {
        $status = $this->_object->getStatus();

        $this->assertFalse($status);
    }

    /**
     * Test get status error
     *
     * @return void
     */
    public function testGetStatusError()
    {
        $settings = array(
            'standard' => 'PSR2',
            'path'     => '.',
        );

        $this->_object = new MockQisModuleCodingstandardErrorLevel(
            $this->_getDefaultQisObject(array()),
            $settings
        );

        $status = $this->_object->getStatus();

        $this->assertFalse($status);
    }

    /**
     * Create object
     *
     * @param bool $initialize Whether to initialize
     * @param Qi_Console_ArgV $args Arguments to pass to object
     * @return Codingstandard
     */
    protected function _createObject($initialize = true, $args = array())
    {
        $settings = array(
            'standard' => 'PSR2',
            'path'     => '.',
        );

        $this->_object = new MockQisModuleCodingstandard(
            $this->_getDefaultQisObject($args),
            $settings
        );

        if ($initialize) {
            $this->_object->initialize();
        }
    }

    /**
     * Get default qis object
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
