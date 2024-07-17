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
        $this->_registerCommands();
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
class MockModule implements ModuleInterface
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
        return [];
    }

    /**
     * Get args from last invocation
     *
     * @return string
     */
    public function getArgs()
    {
        return '';
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
    public function setUp(): void
    {
        $args     = new Qi_Console_ArgV([]);
        $terminal = new Qi_Console_Terminal();

        $this->object = new MockQis($args, $terminal);
    }

    /**
     * Test successful construction
     *
     * @return void
     */
    public function testConstructBothArgs()
    {
        $args     = new Qi_Console_ArgV([]);
        $terminal = new Qi_Console_Terminal();

        $this->object = new Qis($args, $terminal);
        $this->assertTrue(is_object($this->object));
    }

    /**
     * Test get the version
     *
     * @return void
     */
    public function testGetVersion()
    {
        $version = $this->object->getVersion();

        $this->assertStringContainsString('1.2', $version);
    }

    /**
     * Test get verbose with default setting
     *
     * @return void
     */
    public function testIsVerboseDefaultSetting()
    {
        $verbose = $this->object->isVerbose();

        $this->assertFalse($verbose);
    }

    /**
     * Get terminal
     *
     * @return void
     */
    public function testGetTerminal()
    {
        $terminal = $this->object->getTerminal();

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
        $config   = $this->object->getConfig();
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
        $this->object->setConfig($config);
        $actual = $this->object->getConfig();
        $this->assertEquals($config, $actual);
    }

    /**
     * Test getting the project qis root path
     *
     * @return void
     */
    public function testGetProjectQisRoot()
    {
        $root = $this->object->getProjectQisRoot();

        $this->assertStringContainsString('tests/.qis', $root);
    }

    /**
     * Test register commands
     *
     * @return void
     */
    public function testRegisterCommands()
    {
        $this->object->registerCommands();

        $commands = $this->object->getCommands();

        $expected = [
            'all', 'help', 'history', 'init', 'modules', 'summary',
        ];

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

        $count = $this->object->registerModules($modules);
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

        $count = $this->object->registerModules($modules);
        $this->assertEquals(0, $count);
    }

    /**
     * Test register modules
     *
     * @return void
     */
    public function testRegisterModulesFailsSilently()
    {
        $modules = [
            'Abc' => [],
        ];

        ob_start();
        $count  = $this->object->registerModules($modules);
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
        $modules = [
            'Mockmodule' => [
                'class' => 'Qis\\Tests\\MockModule',
            ],
        ];

        $count = $this->object->registerModules($modules);

        $this->assertEquals(1, $count);
    }

    /**
     * Test register module with command
     *
     * @return void
     */
    public function testRegisterModuleWithCommand()
    {
        $modules = [
            'Mockmodule' => [
                'class' => 'Qis\\Tests\\MockModule',
                'command' => 'mock',
            ],
        ];

        $count = $this->object->registerModules($modules);

        $this->assertEquals(1, $count);
    }

    /**
     * Test register module with no class name
     *
     * @return void
     */
    public function testRegisterModuleWithNoClassFile()
    {
        $modules = [
            'Mockmodule' => [
                'class'   => 'MockityMockMock',
                'command' => 'mock',
            ],
        ];

        ob_start();
        $count  = $this->object->registerModules($modules);
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
        $file = 'Mockmodule.php';
        file_put_contents($file, '<' . '?php //nothing');

        $modules = [
            'Mockmodule' => [
                'class' => 'MockityMockMock',
                'command' => 'mock',
            ],
        ];

        ob_start();
        $count  = $this->object->registerModules($modules);
        $result = ob_get_contents();
        ob_end_clean();

        unlink($file);

        $this->assertEquals(0, $count);
        $this->assertStringContainsString('Class MockityMockMock not found', $result);
    }

    /**
     * Test execute with initialized project
     *
     * @return void
     */
    public function testExecuteWithoutInitializedProject()
    {
        ob_start();
        $this->object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('No project config file found', $result);
    }

    /**
     * Test execute with modules
     *
     * @return void
     */
    public function testExecuteWithModules()
    {
        $config = new Config();

        $config->set('project_name', 'testfoo');

        $config->set('modules', [
            'Mockmodule' => [
                'class'   => 'MockQisModule',
                'command' => 'mock',
            ],
        ]);

        $this->object->setConfig($config);

        ob_start();
        $this->object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Failed to load module Mockmodule', $result);
    }

    /**
     * Test execute show help
     *
     * @return void
     */
    public function testExecuteShowHelp()
    {
        $argv = [
            'command',
            '--help',
        ];

        $args     = new Qi_Console_ArgV($argv);
        $terminal = new Qi_Console_Terminal();

        $this->object = new MockQis($args, $terminal);

        ob_start();
        $this->object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Usage: qis', $result);
    }

    /**
     * Test execute show help with project name
     *
     * @return void
     */
    public function testExecuteShowHelpWithProjectName()
    {
        $argv = [
            'command',
            '--help',
        ];

        $args     = new Qi_Console_ArgV($argv);
        $terminal = new Qi_Console_Terminal();

        $this->object = new MockQis($args, $terminal);

        // Setup and attach config
        $config = new Config();

        $config->set('project_name', 'testfoo');

        $config->set('modules', [
            'Mockmodule' => [
                'class'   => '\\Qis\\Tests\\MockModule',
                'command' => 'mock',
            ],
        ]);
        $this->object->setConfig($config);

        ob_start();
        $this->object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Usage: qis', $result);
        $this->assertStringContainsString('Project: testfoo', $result);
    }

    /**
     * Test execute show help with config but no project name
     *
     * @return void
     */
    public function testExecuteShowHelpWithConfigButNoProjectName()
    {
        $argv = [
            'command',
            '--help',
        ];

        $args     = new Qi_Console_ArgV($argv);
        $terminal = new Qi_Console_Terminal();

        $this->object = new MockQis($args, $terminal);

        // Setup and attach config
        $config = new Config();

        $config->set('modules', [
            'Mockmodule' => [
                'class'   => 'MockQisModule',
                'command' => 'mock',
            ],
        ]);
        $this->object->setConfig($config);

        ob_start();
        $this->object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Usage: qis', $result);
        $this->assertStringNotContainsString('testfoo', $result);
    }

    /**
     * Test execute show version
     *
     * @return void
     */
    public function testExecuteShowVersion()
    {
        $argv = [
            'command',
            '--version',
        ];

        $args     = new Qi_Console_ArgV($argv);
        $terminal = new Qi_Console_Terminal();

        $this->object = new MockQis($args, $terminal);

        // Setup and attach config
        $config = new Config();

        $config->set('project_name', 'testfoo');

        $config->set('modules', [
            'Mockmodule' => [
                'class'   => 'MockQisModule',
                'command' => 'mock',
            ],
        ]);

        $this->object->setConfig($config);

        ob_start();
        $this->object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('1.2', $result);
        $this->assertStringNotContainsString('testfoo', $result);
    }

    /**
     * Test that setting the verbose parameter works properly
     *
     * @return void
     */
    public function testExecuteVerboseMode()
    {
        $rules    = ['verbose|v' => 'verbose'];
        $args     = new Qi_Console_ArgV(['cmd', '-v'], $rules);
        $terminal = new Qi_Console_Terminal();

        $this->object = new MockQis($args, $terminal);

        ob_start();
        $this->object->execute();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($this->object->isVerbose());

        $this->assertStringContainsString('No project config file found', $result);
    }

    /**
     * Test log
     *
     * @return void
     */
    public function testLog()
    {
        $rules    = ['verbose|v' => 'verbose'];
        $args     = new Qi_Console_ArgV(['cmd', '-v'], $rules);
        $terminal = new Qi_Console_Terminal();

        $this->object = new MockQis($args, $terminal);

        ob_start();
        $this->object->execute();
        $this->object->log('A penny saved is a penny earned.');
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('A penny saved is a penny earned.', $result);
    }
}
