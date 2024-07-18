<?php

/**
 * Test the Qis All Command
 *
 * @package Qis
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qis\Tests\Command;

use Qis\Tests\BaseTestCase;
use Qis\Command\All;
use Qis\ModuleInterface;
use Qis\Config;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Mock Qis Module for All subcommand tests
 *
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleBaseForAll implements ModuleInterface
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
     * Get metrics
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
 * Mock Qis Module Aa
 *
 * @uses MockQisModuleBaseForAll
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleAa extends MockQisModuleBaseForAll
{
    /**
     * Execute
     *
     * @param Qi_Console_ArgV $args Arguments
     * @return int
     */
    public function execute(Qi_Console_ArgV $args)
    {
        echo 'execute aa';
        return 1;
    }
}

/**
 * Mock Qis Module Kk
 *
 * @uses MockQisModuleBaseForAll
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MockQisModuleKk extends MockQisModuleBaseForAll
{
    /**
     * Execute
     *
     * @param Qi_Console_ArgV $args Arguments
     * @return int
     */
    public function execute(Qi_Console_ArgV $args)
    {
        echo 'execute kk';
        return 11;
    }
}

/**
 * Qis Command All test
 *
 * @uses \Qis\Tests\BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class AllTest extends BaseTestCase
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

        $this->object = new All($this->qis, $settings);
    }

    /**
     * Test get name
     *
     * @return void
     */
    public function testGetName()
    {
        $name = All::getName();

        $this->assertEquals('all', $name);
    }

    /**
     * Test initialize
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->object->initialize();

        $this->assertTrue(true);
    }

    /**
     * Test execute with empty args
     *
     * @return void
     */
    public function testExecuteEmptyArgs()
    {
        $args = new Qi_Console_ArgV([]);

        $result = $this->object->execute($args);

        $this->assertEquals(0, $result);
    }

    /**
     * Test get help message
     *
     * @return void
     */
    public function testGetHelpMessage()
    {
        $result = $this->object->getHelpMessage();

        $this->assertTrue(is_string($result));
    }

    /**
     * Test get help message
     *
     * @return void
     */
    public function testGetExtendedHelpMessage()
    {
        $result = $this->object->getExtendedHelpMessage();

        $this->assertTrue(is_string($result));
    }

    /**
     * Test execute all with default build order
     *
     * @return void
     */
    public function testExecuteAllWithDefaultBuildOrder()
    {
        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('execute aa', $result);
        $this->assertStringContainsString('execute kk', $result);
    }

    /**
     * Gest execute all with configed build order
     *
     * @return void
     */
    public function testExecuteAllWithConfigedBuildOrder()
    {
        $config = new Config();
        $config->set('build_order', 'cs');

        $this->qis->setConfig($config);

        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('execute aa', $result);
        $this->assertStringNotContainsString('execute kk', $result);
    }

    /**
     * The names of the commands in the list are trimmed
     *
     * @return void
     */
    public function testGetBuildOrderWithSpacesInBetweenCommas()
    {
        $config = new Config();
        $config->set('build_order', 'cs,  coverage,  test');

        $this->qis->setConfig($config);

        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('execute aa', $result);
        $this->assertStringContainsString('execute kk', $result);
    }

    /**
     * This will just end up running no modules
     *
     * @return void
     */
    public function testGetBuildOrderWithInvalidBuildOrder()
    {
        $config = new Config();
        $config->set('build_order', ',,,,,,');

        $this->qis->setConfig($config);

        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringNotContainsString('execute aa', $result);
        $this->assertStringNotContainsString('execute kk', $result);
    }

    /**
     * This will run no modules, since no commands match
     *
     * @return void
     */
    public function testGetBuildOrderWithAnotherInvalidBuildOrder()
    {
        $config = new Config();
        $config->set(
            'build_order',
            '$#@$#@^%$#^%$#%$#$#@$,$#@!$#@!$#@!$#@!,8893438439483948393893'
        );

        $this->qis->setConfig($config);

        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringNotContainsString('execute aa', $result);
        $this->assertStringNotContainsString('execute kk', $result);
    }

    /**
     * If the build order is empty, it will use the default
     *
     * @return void
     */
    public function testGetBuildOrderWithEmptyBuildOrder()
    {
        $config = new Config();
        $config->set('build_order', '');

        $this->qis->setConfig($config);

        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('execute aa', $result);
        $this->assertStringContainsString('execute kk', $result);
    }

    /**
     * If the build order is a bunch of whitespace, it'll use the default
     *
     * @return void
     */
    public function testGetBuildOrderWithBlankBuildOrder()
    {
        $config = new Config();
        $config->set('build_order', '    ');

        $this->qis->setConfig($config);

        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('execute aa', $result);
        $this->assertStringContainsString('execute kk', $result);
    }

    /**
     * If the build order is an array, it will use the default
     *
     * @return void
     */
    public function testGetBuildOrderWithArrayBuildOrder()
    {
        $config = new Config();
        $config->set('build_order', ['nofooling']);

        $this->qis->setConfig($config);

        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV(array());

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('execute aa', $result);
        $this->assertStringContainsString('execute kk', $result);
    }

    /**
     * Test rule
     *
     * @return void
     */
    public function testRule()
    {
        $this->setupSomeDefaultModules();

        $args = new Qi_Console_ArgV(array());

        list($result, $status) = $this->execute($args);

        $expected = str_repeat('%', 80);

        $this->assertStringContainsString($expected, $result);
    }

    /**
     * Run execute on the object and return the buffered output and status
     *
     * @param Qi_Console_ArgV $args ARguments
     * @return array
     */
    protected function execute($args)
    {
        ob_start();
        $status = $this->object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        return array($result, $status);
    }

    /**
     * Setup some default mock modules and register with qis
     *
     * @return void
     */
    protected function setupSomeDefaultModules()
    {
        $modules = array(
            'Aa' => array(
                'class' => 'Qis\\Tests\\Command\\MockQisModuleAa',
                'command' => 'cs',
            ),
            'Kk' => array(
                'class' => 'Qis\\Tests\\Command\\MockQisModuleKk',
                'command' => 'test',
            ),
            'Aa2' => array(
                'class' => 'Qis\\Tests\\Command\\MockQisModuleAa',
                'command' => 'coverage',
            ),
        );

        $this->qis->registerModules($modules);
    }
}
