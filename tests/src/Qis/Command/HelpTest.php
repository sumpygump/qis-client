<?php

/**
 * Qis Command Help test class file
 *
 * @package Qis
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qis\Tests\Command;

use Qis\Tests\BaseTestCase;
use Qis\Command\Help;
use Qis\ModuleInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Mock Module class for Help subcommand
 *
 * @uses \Qis\ModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class MockQisModuleBaseForHelp implements ModuleInterface
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
        $qis == null;
        $settings == null;
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
 * Qis Command Help Test cases
 *
 * @uses \Qis\Tests\BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class HelpTest extends BaseTestCase
{
    public $qis;

    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        $args     = new Qi_Console_ArgV([]);
        $terminal = new Qi_Console_Terminal();

        $this->qis = new Qis($args, $terminal);

        $this->object = new Help($this->qis);
    }

    /**
     * Get name should return the default command name
     *
     * @return void
     */
    public function testGetName()
    {
        $name = Help::getName();

        $this->assertEquals('help', $name);
    }

    /**
     * Initialize doesn't do anything, but it should be available
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->object->initialize();

        $this->assertTrue(true);
    }

    /**
     * Test execute regular help
     *
     * @return void
     */
    public function testExecuteRegularHelp()
    {
        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('Usage: qis <subcommand', $result);
        $this->assertStringContainsString('Global Options:', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute regular help with modules registered
     *
     * @return void
     */
    public function testExecuteRegularHelpWithModulesRegistered()
    {
        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('Usage: qis <subcommand', $result);
        $this->assertStringContainsString("Modules:", $result);
        $this->assertStringContainsString("foobar : help message", $result);
        $this->assertStringContainsString("Global Options:", $result);
        $this->assertEquals(0, $status);
    }

    /**
     * If the module doesn't exist, it will throw an exception
     *
     * @return void
     */
    public function testExecuteContextualHelpModuleNotFound()
    {
        $argv = [
            './qis',
            'help',
            'foobar',
        ];

        $args = new Qi_Console_ArgV($argv);

        $this->expectException(\Qis\CommandException::class);
        $status = $this->object->execute($args);
    }

    /**
     * Test execute contextual help when module exists
     *
     * @return void
     */
    public function testExecuteContextualHelpModuleExists()
    {
        $argv = [
            './qis',
            'help',
            'foobar',
        ];

        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString("Help for module 'foobar'", $result);
        $this->assertStringContainsString("extended help message", $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Get help message should return a string
     *
     * @return void
     */
    public function testGetHelpMessage()
    {
        $message = $this->object->getHelpMessage();

        $this->assertTrue(is_string($message));
    }

    /**
     * Test get extended help message
     *
     * @return void
     */
    public function testGetExtendedHelpMessage()
    {
        $result = $this->object->getExtendedHelpMessage();

        $this->assertTrue(is_string($result));
    }

    /**
     * Run execute on the object and return the buffered output and status
     *
     * @param Qi_Console_ArgV $args Arguments
     * @return array
     */
    protected function execute($args)
    {
        ob_start();
        $status = $this->object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        return [$result, $status];
    }

    /**
     * Setup some default mock modules and register with qis
     *
     * @return void
     */
    protected function setupSomeDefaultModules()
    {
        $modules = [
            'mock' => [
                'class' => 'Qis\\Tests\\Command\\MockQisModuleBaseForHelp',
                'command' => 'foobar',
            ],
        ];

        $this->qis->registerModules($modules);
    }
}
