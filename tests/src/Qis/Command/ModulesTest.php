<?php
/**
 * Qis Command Modules test class file
 *
 * @package Qis
 */

namespace Qis\Tests\Command;

use \BaseTestCase;
use Qis\Command\Modules;
use Qis\ModuleInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Mock Module class for Init subcommand
 *
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
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
        return array();
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
class ModulesTest extends BaseTestCase
{
    public $_qis;

    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        $args     = new Qi_Console_ArgV(array());
        $terminal = new Qi_Console_Terminal();

        $this->_qis = new Qis($args, $terminal);

        $settings = array();

        $this->_object = new Modules($this->_qis, $settings);
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
        $args = new Qi_Console_ArgV(array());

        list($result, $status) = $this->_execute($args);

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
        $this->_setupSomeDefaultModules();

        $args = new Qi_Console_ArgV(array());

        list($result, $status) = $this->_execute($args);

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
     * @param Qis_Console_ArgV $args Arguments
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
                'class' => 'Qis\\Tests\\Command\\MockQisModuleBaseForModules',
                'command' => 'foobar',
            ),
        );

        $this->_qis->registerModules($modules);
    }
}
