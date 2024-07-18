<?php

/**
 * Qis Command Modules test class file
 *
 * @package Qis
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qis\Tests\Command;

use Qis\Tests\BaseTestCase;
use Qis\Command\Modules;
use Qis\ModuleInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Mock Module class for Init subcommand
 *
 * @uses \Qis\ModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class MockQisModuleBaseForModules implements ModuleInterface
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
 * Qis Command Init Test cases
 *
 * @uses \Qis\Tests\BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class ModulesTest extends BaseTestCase
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

        $settings = [];

        $this->object = new Modules($this->qis, $settings);
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
     * Test get name
     *
     * @return void
     */
    public function testGetName()
    {
        $name = Modules::getName();

        $this->assertEquals('modules', $name);
    }

    /**
     * Test execute default
     *
     * @return void
     */
    public function testExecuteDefault()
    {
        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertEquals('', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with registered modules
     *
     * @return void
     */
    public function testExecuteWithRegisteredModules()
    {
        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('|  Module', $result);
        $this->assertStringContainsString('|  Qis\\Tests\\Command\\MockQisModuleBaseForModules  |', $result);
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
     * @param Qis_Console_ArgV $args Arguments
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
                'class' => 'Qis\\Tests\\Command\\MockQisModuleBaseForModules',
                'command' => 'foobar',
            ],
        ];

        $this->qis->registerModules($modules);
    }
}
