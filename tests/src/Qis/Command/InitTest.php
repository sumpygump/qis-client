<?php

/**
 * Qis Command Init test class file
 *
 * @package Qis
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qis\Tests\Command;

use Qis\Tests\BaseTestCase;
use Qis\Command\Init;
use Qis\ModuleInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Qis Command Init Test cases
 *
 * @uses \Qis\Tests\BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class InitTest extends BaseTestCase
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

        $this->object = new Init($this->qis);
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown(): void
    {
        passthru('rm -rf .qis');
    }

    /**
     * Get name should return the default command name
     *
     * @return void
     */
    public function testGetName()
    {
        $name = Init::getName();

        $this->assertEquals('init', $name);
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
     * Text execute default
     *
     * @return void
     */
    public function testExecuteDefault()
    {
        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('Initializing project...', $result);
        $this->assertEquals(0, $status);
    }

    /**
     * Test execute when path already exists
     *
     * @return void
     */
    public function testExecuteWhenPathAlreadyExists()
    {
        mkdir('.qis');

        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringContainsString('Initializing project...', $result);
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
}
