<?php
/**
 * Qis Command Summary test class file 
 *
 * @package Qis
 */

namespace Qis\Tests\Command;

use \BaseTestCase;
use Qis\Command\Summary;
use Qis\ModuleInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Mock Module class for Summary subcommand
 * 
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleBaseForSummary implements ModuleInterface
{
    /**
     * Get default ini
     *
     * @return void
     */
    public static function getDefaultIni()
    {
        echo "f=o\n";
    }

    /**
     * Constructor
     *
     * @param Qis $qis Qis object
     * @param mixed $settings Config settings
     * @return void
     */
    public function __construct(Qis $qis, $settings)
    {
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * Execute
     *
     * @param Qi_Console_ArgV $args Arguments
     * @return int
     */
    public function execute(Qi_Console_ArgV $args)
    {
        return 0;
    }

    /**
     * Get help message
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return 'help message';
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        return 'extended help message';
    }

    /**
     * Get summary
     *
     * @param bool $short Short summary
     * @return string
     */
    public function getSummary($short = false)
    {
        if ($short) {
            return 'short summary';
        }
        return 'long summary';
    }

    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus()
    {
        return true;
    }

    /**
     * getMetrics
     *
     * @return void
     */
    public function getMetrics()
    {
        return array();
    }
}

/**
 * MockQisModuleSummaryFalseStatus
 *
 * @uses MockQisModuleBaseForSummary
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleSummaryFalseStatus
    extends MockQisModuleBaseForSummary
{
    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus()
    {
        return false;
    }
}

/**
 * Qis Command Init Test cases
 * 
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class SummaryTest extends BaseTestCase
{
    /**
     * Setup before each test
     * 
     * @return void
     */
    public function setUp()
    {
        $args     = new Qi_Console_ArgV(array());
        $terminal = new Qi_Console_Terminal();

        $this->_qis = new Qis($args, $terminal);

        $settings = array();

        $this->_object = new Summary($this->_qis, $settings);
    }

    /**
     * Tear down after each test
     * 
     * @return void
     */
    public function tearDown()
    {
    }

    /**
     * Get name should return the default command name
     * 
     * @return void
     */
    public function testGetName()
    {
        $name = Summary::getName();

        $this->assertEquals('summary', $name);
    }

    /**
     * Initialize doesn't do anything, but it should be available
     * 
     * @return void
     */
    public function testInitialize()
    {
        $this->_object->initialize();

        $this->assertTrue(true);
    }

    /**
     * Test execute default
     *
     * @return void
     */
    public function testExecuteDefault()
    {
        $args = new Qi_Console_ArgV(array());

        list($result, $status) = $this->_execute($args);

        $this->assertEquals("\n", $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute set no color
     *
     * @return void
     */
    public function testExecuteSetNoColor()
    {
        $argv = array(
            './qis',
            'summary',
            '--no-color',
        );
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->_execute($args);

        $this->assertEquals("\n", $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute set short
     *
     * @return void
     */
    public function testExecuteSetShort()
    {
        $argv = array(
            './qis',
            'summary',
            '--short',
        );
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->_execute($args);

        $expected = str_repeat('-', 32) . "\n";

        $this->assertContains($expected, $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with registered modules
     *
     * @return void
     */
    public function testExecuteWithRegisteredModules()
    {
        $this->_setupSomeDefaultModules();

        $args = new Qi_Console_ArgV(array());

        list($result, $status) = $this->_execute($args);

        $this->assertContains('long summary', $result);
        $this->assertContains('short summary', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with registered modules no color
     *
     * @return void
     */
    public function testExecuteWithRegisteredModulesNoColor()
    {
        $this->_setupSomeDefaultModules();

        $argv = array(
            './qis',
            'summary',
            '--no-color',
        );
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->_execute($args);

        $this->assertContains('long summary', $result);
        $this->assertContains('short summary', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with registered modules short
     *
     * @return void
     */
    public function testExecuteWithRegisteredModulesShort()
    {
        $this->_setupSomeDefaultModules();

        $argv = array(
            './qis',
            'summary',
            '--short',
        );
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->_execute($args);

        $expected = str_repeat('-', 32) . "\n";
        $this->assertContains($expected, $result);

        $this->assertNotContains('long summary', $result);
        $this->assertContains('short summary', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with registered modules short no color
     *
     * @return void
     */
    public function testExecuteWithRegisteredModulesShortNoColor()
    {
        $this->_setupSomeDefaultModules();

        $argv = array(
            './qis',
            'summary',
            '--short',
            '--no-color',
        );
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->_execute($args);

        $expected = str_repeat('-', 32) . "\n";
        $this->assertContains($expected, $result);

        $this->assertNotContains('long summary', $result);
        $this->assertContains('short summary', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with specified module
     *
     * @return void
     */
    public function testExecuteWithSpecifiedModule()
    {
        $this->_setupSomeDefaultModules();

        $argv = array(
            './qis',
            'summary',
            'mockmock',
        );
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->_execute($args);

        $this->assertContains('long summary', $result);
        $this->assertContains('short summary', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Get help message should return a string
     * 
     * @return void
     */
    public function testGetHelpMessage()
    {
        $message = $this->_object->getHelpMessage();

        $this->assertTrue(is_string($message));
    }

    /**
     * Test get extended help message
     *
     * @return void
     */
    public function testGetExtendedHelpMessage()
    {
        $result = $this->_object->getExtendedHelpMessage();

        $this->assertTrue(is_string($result));
    }

    /**
     * Run execute on the object and return the buffered output and status
     * 
     * @param Qi_Console_ArgV $args Arguments
     * @return void
     */
    protected function _execute($args)
    {
        ob_start();
        $status = $this->_object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        return array($result, $status);
    }

    /**
     * Setup some default mock modules and register with qis
     * 
     * @return void
     */
    protected function _setupSomeDefaultModules()
    {
        $modules = array(
            'mock' => array(
                'class' => 'MockQisModuleBaseForSummary',
                'command' => 'mockmock',
            ),
            'mfalse' => array(
                'class' => 'MockQisModuleSummaryFalseStatus',
                'command' => 'mfalse',
            ),
        );

        $this->_qis->registerModules($modules);
    }
}

