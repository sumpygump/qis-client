<?php
/**
 * Qis Test class file
 *
 * @package Qis
 */

namespace Qis\Tests;

use BaseTestCase;
use Qis\Qis;
use Qis\ModuleInterface;
use Qis\Config;
use Qi_Console_ArgV;
use Qi_Console_Terminal;
use StdClass;

/**
 * Mock Qis class
 *
 * Exposes protected methods for testing
 *
 * @uses Qis
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQis extends Qis
{
    /**
     * Register commands
     *
     * @return void
     */
    public function registerCommands()
    {
        return $this->_registerCommands();
    }
}

/**
 * Mock Qis Module
 *
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModule implements ModuleInterface
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
     * @param array $settings Config settings
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
 * Qis Test class
 *
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class QisTest extends BaseTestCase
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

        $this->_object = new MockQis($args, $terminal);
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
     * Test successful construction
     *
     * @return void
     */
    public function testConstructBothArgs()
    {
        $args     = new Qi_Console_ArgV(array());
        $terminal = new Qi_Console_Terminal();

        $this->_object = new Qis($args, $terminal);
        $this->assertTrue(is_object($this->_object));
    }

    /**
     * Test get the version
     *
     * @return void
     */
    public function testGetVersion()
    {
        $version = $this->_object->getVersion();

        $this->assertEquals('1.0.11', $version);
    }

    /**
     * Test get verbose with default setting
     *
     * @return void
     */
    public function testIsVerboseDefaultSetting()
    {
        $verbose = $this->_object->isVerbose();

        $this->assertFalse($verbose);
    }

    /**
     * Get terminal
     *
     * @return void
     */
    public function testGetTerminal()
    {
        $terminal = $this->_object->getTerminal();

        $this->assertTrue(is_object($terminal));
    }

    /**
     * Get config
     *
     * @return void
     */
    public function testGetConfig()
    {
        $expected = new Config();
        $config   = $this->_object->getConfig();
        $this->assertEquals($expected, $config);
    }

    /**
     * Test set config
     *
     * @return void
     */
    public function testSetConfig()
    {
        $config = new Config();
        $this->_object->setConfig($config);
        $actual = $this->_object->getConfig();
        $this->assertEquals($config, $actual);
    }

    /**
     * Test getting the project qis root path
     *
     * @return void
     */
    public function testGetProjectQisRoot()
    {
        $root = $this->_object->getProjectQisRoot();

        $this->assertContains('tests/.qis', $root);
    }

    /**
     * Test register commands
     *
     * @return void
     */
    public function testRegisterCommands()
    {
        $this->_object->registerCommands();

        $commands = $this->_object->getCommands();

        $expected = array(
            'all', 'help', 'history', 'init', 'modules', 'summary',
        );

        $keys = array_keys($commands);

        $this->assertEquals($expected, $keys);

        foreach ($commands as $name => $command) {
            $this->assertTrue(is_object($command));
        }
    }

    /**
     * Test registering modules with a string as the parameter
     *
     * @return void
     */
    public function testRegisterModulesStringParam()
    {
        $modules = 'Codingstandard';

        $count = $this->_object->registerModules($modules);
        $this->assertFalse($count);
    }

    /**
     * Test register modules empty object
     *
     * @return void
     */
    public function testRegisterModulesEmptyObject()
    {
        $modules = new StdClass();

        $count = $this->_object->registerModules($modules);
        $this->assertEquals(0, $count);
    }

    /**
     * Test register modules
     *
     * @return void
     */
    public function testRegisterModulesFailsSilently()
    {
        $modules = array(
            'Codingstandard' => array(),
        );

        ob_start();
        $count  = $this->_object->registerModules($modules);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(0, $count);
    }

    /**
     * Test registering a module
     *
     * @return void
     */
    public function testRegisterModule()
    {
        $modules = array(
            'Mockmodule' => array(
                'class' => 'MockQisModule',
            ),
        );

        $count = $this->_object->registerModules($modules);

        $this->assertEquals(1, $count);
    }

    /**
     * Test register module with command
     *
     * @return void
     */
    public function testRegisterModuleWithCommand()
    {
        $modules = array(
            'Mockmodule' => array(
                'class' => 'MockQisModule',
                'command' => 'mock',
            ),
        );

        $count = $this->_object->registerModules($modules);

        $this->assertEquals(1, $count);
    }

    /**
     * Test register module with no class name
     *
     * @return void
     */
    public function testRegisterModuleWithNoClassFile()
    {
        $modules = array(
            'Mockmodule' => array(
                'class'   => 'MockityMockMock',
                'command' => 'mock',
            ),
        );

        ob_start();
        $count  = $this->_object->registerModules($modules);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(0, $count);
    }

    /**
     * Test register module with file but no class
     *
     * @return void
     */
    public function testRegisterModuleWithFileButNoClass()
    {
        $file = 'modules/Mockmodule.php';
        file_put_contents($file, '<' . '?php //nothing');

        $modules = array(
            'Mockmodule' => array(
                'class' => 'MockityMockMock',
                'command' => 'mock',
            ),
        );

        ob_start();
        $count  = $this->_object->registerModules($modules);
        $result = ob_get_contents();
        ob_end_clean();

        unlink($file);

        $this->assertEquals(0, $count);
        $this->assertContains('Class MockityMockMock not found', $result);
    }

    /**
     * Test execute with initialized project
     *
     * @return void
     */
    public function testExecuteWithoutInitializedProject()
    {
        ob_start();
        $this->_object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertContains('No project config file found', $result);
    }

    /**
     * Test execute with modules
     *
     * @return void
     */
    public function testExecuteWithModules()
    {
        $config = new Config();

        $config->project_name = 'testfoo';

        $config->modules = array(
            'Mockmodule' => array(
                'class'   => 'MockQisModule',
                'command' => 'mock',
            ),
        );

        $this->_object->setConfig($config);

        ob_start();
        $this->_object->execute();
        $result = ob_get_contents();
        ob_end_clean();
    }

    /**
     * Test execute show help
     *
     * @return void
     */
    public function testExecuteShowHelp()
    {
        $argv = array(
            'command',
            '--help',
        );

        $args     = new Qi_Console_ArgV($argv);
        $terminal = new Qi_Console_Terminal();

        $this->_object = new MockQis($args, $terminal);

        ob_start();
        $this->_object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertContains('Usage: qis', $result);
    }

    /**
     * Test execute show help with project name
     *
     * @return void
     */
    public function testExecuteShowHelpWithProjectName()
    {
        $argv = array(
            'command',
            '--help',
        );

        $args     = new Qi_Console_ArgV($argv);
        $terminal = new Qi_Console_Terminal();

        $this->_object = new MockQis($args, $terminal);

        // Setup and attach config
        $config = new Config();

        $config->project_name = 'testfoo';

        $config->modules = array(
            'Mockmodule' => array(
                'class'   => 'MockQisModule',
                'command' => 'mock',
            ),
        );
        $this->_object->setConfig($config);

        ob_start();
        $this->_object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertContains('Usage: qis', $result);
        $this->assertContains('testfoo', $result);
    }

    /**
     * Test execute show help with config but no project name
     *
     * @return void
     */
    public function testExecuteShowHelpWithConfigButNoProjectName()
    {
        $argv = array(
            'command',
            '--help',
        );

        $args     = new Qi_Console_ArgV($argv);
        $terminal = new Qi_Console_Terminal();

        $this->_object = new MockQis($args, $terminal);

        // Setup and attach config
        $config = new Config();

        $config->modules = array(
            'Mockmodule' => array(
                'class'   => 'MockQisModule',
                'command' => 'mock',
            ),
        );
        $this->_object->setConfig($config);

        ob_start();
        $this->_object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertContains('Usage: qis', $result);
        $this->assertNotContains('testfoo', $result);
    }

    /**
     * Test execute show version
     *
     * @return void
     */
    public function testExecuteShowVersion()
    {
        $argv = array(
            'command',
            '--version',
        );

        $args     = new Qi_Console_ArgV($argv);
        $terminal = new Qi_Console_Terminal();

        $this->_object = new MockQis($args, $terminal);

        // Setup and attach config
        $config = new Config();

        $config->project_name = 'testfoo';

        $config->modules = array(
            'Mockmodule' => array(
                'class'   => 'MockQisModule',
                'command' => 'mock',
            ),
        );

        $this->_object->setConfig($config);

        ob_start();
        $this->_object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertContains('1.0.11', $result);
        $this->assertNotContains('testfoo', $result);
    }

    /**
     * Test that setting the verbose parameter works properly
     *
     * @return void
     */
    public function testExecuteVerboseMode()
    {
        $rules    = array('verbose|v' => 'verbose');
        $args     = new Qi_Console_ArgV(array('cmd', '-v'), $rules);
        $terminal = new Qi_Console_Terminal();

        $this->_object = new MockQis($args, $terminal);

        ob_start();
        $this->_object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($this->_object->isVerbose());

        $this->assertContains('No project config file found', $result);
    }

    /**
     * Test log
     *
     * @return void
     */
    public function testLog()
    {
        $rules    = array('verbose|v' => 'verbose');
        $args     = new Qi_Console_ArgV(array('cmd', '-v'), $rules);
        $terminal = new Qi_Console_Terminal();

        $this->_object = new MockQis($args, $terminal);

        ob_start();
        $this->_object->execute();
        $this->_object->log('A penny saved is a penny earned.');
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertContains('A penny saved is a penny earned.', $result);
    }
}
