<?php
/**
 * Qis Coverage module test
 *
 * @package Qis
 */

/**
 * @see Coverage
 */
require_once 'modules/Coverage.php';

/**
 * Mock Qis Module Coverage
 *
 * @uses Qis_Module_Coverage
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleCoverage extends Qis_Module_Coverage
{
}

/**
 * Qis_Module_CoverageTest
 *
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qis_Module_CoverageTest extends BaseTestCase
{
    /**
     * Setup before each test
     * 
     * @return void
     */
    public function setUp()
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
    public function tearDown()
    {
        $path = realpath('.') . DIRECTORY_SEPARATOR . '.qis';
        if (file_exists($path)) {
            passthru("rm -rf $path");
        }
    }

    /**
     * Test constructor with no arguments
     *
     * @expectedException PHPUnit_Framework_Error
     * @return void
     */
    public function testConstructorWithNoArguments()
    {
        $this->_object = new Qis_Module_Coverage();
    }

    /**
     * Test get default ini
     *
     * @return void
     */
    public function testGetDefaultIni()
    {
        $defaultIni = Qis_Module_Coverage::getDefaultIni();

        $this->assertContains(
            '; Module for code coverage of unit tests', $defaultIni
        );
        $this->assertContains('coverage.ignorePaths=', $defaultIni);
    }

    /**
     * testExecuteNoArgument
     *
     * @expectedException PHPUnit_Framework_Error
     * @return void
     */
    public function testExecuteNoArgument()
    {
        ob_start();
        $this->_object->execute();
        $result = ob_get_contents();
        ob_end_clean();
    }

    /**
     * testExecute
     *
     * @expectedException Qis_Module_CoverageException
     * @return void
     */
    public function testExecute()
    {
        $args = array();
        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->_object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();
    }

    /**
     * Test execute with argument
     *
     * @expectedException Qis_Module_CoverageException
     * @return void
     */
    public function testExecuteWithArgument()
    {
        $args = array(
            'coverage',
            'foo',
            'wong.txt',
        );

        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->_object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();
    }

    /**
     * Get get help message
     *
     * @return void
     */
    public function testGetHelpMessage()
    {
        $help = $this->_object->getHelpMessage();

        $this->assertContains('Show code coverage for unit tests.', $help);
    }

    /**
     * Test get extended help message
     *
     * @return void
     */
    public function testGetExtendedHelpMessage()
    {
        $help = $this->_object->getExtendedHelpMessage();

        $this->assertContains('Usage: coverage [OPTIONS] [filename]', $help);
        $this->assertContains('Valid Options:', $help);
    }

    /**
     * Test get summary
     *
     * @return void
     */
    public function testGetSummary()
    {
        $summary = $this->_object->getSummary();

        $this->assertContains('Coverage results:', $summary);
        $this->assertNotContains('Coverage: ', $summary);
    }

    /**
     * Test get short summary
     *
     * @return void
     */
    public function testGetShortSummary()
    {
        $summary = $this->_object->getSummary(true);

        $this->assertContains('Coverage: ', $summary);
        $this->assertNotContains('Coverage results:', $summary);
    }

    /**
     * Test get status
     *
     * @return void
     */
    public function testGetStatus()
    {
        $status = $this->_object->getStatus();

        $this->assertEquals(0, $status);
    }

    /**
     * Create object
     *
     * @param bool $initialize Whether to initialize
     * @param Qis_Console_ArgV $args Arguments
     * @return Qis_Module_Coverage
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
     * @return void
     */
    protected function _getDefaultQisObject($args = array())
    {
        $args     = new Qi_Console_ArgV($args);
        $terminal = new Qi_Console_Terminal();

        Qis::$exit = false;
        return new Qis($args, $terminal);
    }
}
