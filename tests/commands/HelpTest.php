<?php
/**
 * Qis Command Help test class file 
 *
 * @package Qis
 */

/**
 * All Command class
 */
require_once 'commands/Help.php';

/**
 * Mock Module class for Help subcommand
 * 
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleBaseForHelp implements QisModuleInterface
{
    /**
     * Get Default Ini
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
     * @param mixed $settings Settings
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
 * Qis Command Help Test cases
 * 
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qis_Command_HelpTest extends BaseTestCase
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

        $this->_object = new Qis_Command_Help($this->_qis, $settings);
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
        $name = Qis_Command_Help::getName();

        $this->assertEquals('help', $name);
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
     * Test execute regular help
     *
     * @return void
     */
    public function testExecuteRegularHelp()
    {
        $args = new Qi_Console_ArgV(array());

        list($result, $status) = $this->_execute($args);

        $this->assertContains('Usage: qis <subcommand', $result);
        $this->assertContains('Global Options:', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute regular help with modules registered
     *
     * @return void
     */
    public function testExecuteRegularHelpWithModulesRegistered()
    {
        $this->_setupSomeDefaultModules();

        $args = new Qi_Console_ArgV(array());

        list($result, $status) = $this->_execute($args);

        $this->assertContains('Usage: qis <subcommand', $result);
        $this->assertContains("Modules:\n", $result);
        $this->assertContains("foobar : help message\nUse", $result);
        $this->assertContains("Global Options:", $result);
        $this->assertEquals(0, $status);
    }

    /**
     * If the module doesn't exist, it will throw an exception
     * 
     * @expectedException QisCommandException
     * @return void
     */
    public function testExecuteContextualHelpModuleNotFound()
    {
        $argv = array(
            './qis',
            'help',
            'foobar',
        );

        $args = new Qi_Console_ArgV($argv);

        $status = $this->_object->execute($args);
    }

    /**
     * Test execute contextual help when module exists
     *
     * @return void
     */
    public function testExecuteContextualHelpModuleExists()
    {
        $argv = array(
            './qis',
            'help',
            'foobar',
        );

        $this->_setupSomeDefaultModules();

        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->_execute($args);

        $this->assertContains("foobar: extended help message\nGlobal", $result);
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
     * @return array
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
                'class' => 'MockQisModuleBaseForHelp',
                'command' => 'foobar',
            ),
        );

        $this->_qis->registerModules($modules);
    }
}
