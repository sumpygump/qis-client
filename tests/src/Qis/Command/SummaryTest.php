<?php

/**
 * Qis Command Summary test class file
 *
 * @package Qis
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qis\Tests\Command;

use Qis\Tests\BaseTestCase;
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
 * MockQisModuleSummaryFalseStatus
 *
 * @uses MockQisModuleBaseForSummary
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleSummaryFalseStatus extends MockQisModuleBaseForSummary
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
 * @uses \Qis\Tests\BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class SummaryTest extends BaseTestCase
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

        $this->object = new Summary($this->qis, $settings);
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
        $this->object->initialize();

        $this->assertTrue(true);
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
        $argv = [
            './qis',
            'summary',
            '--no-color',
        ];
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->execute($args);

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
        $argv = [
            './qis',
            'summary',
            '--short',
        ];
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->execute($args);

        $expected = str_repeat('-', 32) . "\n";

        $this->assertStringContainsString($expected, $result);
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

        $this->assertStringContainsString('long summary', $result);
        $this->assertStringContainsString('short summary', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with registered modules no color
     *
     * @return void
     */
    public function testExecuteWithRegisteredModulesNoColor()
    {
        $this->setupSomeDefaultModules();

        $argv = [
            './qis',
            'summary',
            '--no-color',
        ];
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('long summary', $result);
        $this->assertStringContainsString('short summary', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with registered modules short
     *
     * @return void
     */
    public function testExecuteWithRegisteredModulesShort()
    {
        $this->setupSomeDefaultModules();

        $argv = [
            './qis',
            'summary',
            '--short',
        ];
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->execute($args);

        $expected = str_repeat('-', 32) . "\n";
        $this->assertStringContainsString($expected, $result);

        $this->assertStringNotContainsString('long summary', $result);
        $this->assertStringContainsString('short summary', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with registered modules short no color
     *
     * @return void
     */
    public function testExecuteWithRegisteredModulesShortNoColor()
    {
        $this->setupSomeDefaultModules();

        $argv = [
            './qis',
            'summary',
            '--short',
            '--no-color',
        ];
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->execute($args);

        $expected = str_repeat('-', 32) . "\n";
        $this->assertStringContainsString($expected, $result);

        $this->assertStringNotContainsString('long summary', $result);
        $this->assertStringContainsString('short summary', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute with specified module
     *
     * @return void
     */
    public function testExecuteWithSpecifiedModule()
    {
        $this->setupSomeDefaultModules();

        $argv = [
            './qis',
            'summary',
            'mockmock',
        ];
        $args = new Qi_Console_ArgV($argv);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('long summary', $result);
        $this->assertStringContainsString('short summary', $result);
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
                'class' => 'Qis\\Tests\\Command\\MockQisModuleBaseForSummary',
                'command' => 'mockmock',
            ],
            'mfalse' => [
                'class' => 'Qis\\Tests\\Command\\MockQisModuleSummaryFalseStatus',
                'command' => 'mfalse',
            ],
        ];

        $this->qis->registerModules($modules);
    }
}
