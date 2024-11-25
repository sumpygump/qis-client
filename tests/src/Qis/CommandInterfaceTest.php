<?php

/**
 * Test qis command interface
 *
 * @package Qis
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qis\Tests;

use Qis\Tests\BaseTestCase;
use Qis\CommandInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Qis Command
 *
 * @uses \Qis\CommandInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class Command implements CommandInterface
{
    /**
     * Get name
     *
     * @return string
     */
    public static function getName()
    {
        return 'name';
    }

    /**
     * Constructor
     *
     * @param Qis $qis Qis object
     * @return void
     */
    public function __construct(Qis $qis)
    {
        $qis == null;
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
        return '';
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        return '';
    }
}

/**
 * Qis command interface test
 *
 * @uses \Qis\Tests\BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@nerdery.com>
 */
class CommandInterfaceTest extends BaseTestCase
{
    public $qis;

    /**
     * Test get name
     *
     * @return void
     */
    public function testGetName()
    {
        $args     = new Qi_Console_ArgV([]);
        $terminal = new Qi_Console_Terminal();

        $this->qis = new Qis($args, $terminal);

        $command = new Command($this->qis);

        $name = $command->getName();
        $this->assertEquals('name', $name);
    }
}
