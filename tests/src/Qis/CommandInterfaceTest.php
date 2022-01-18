<?php

/**
 * Test qis command interface
 *
 * @package Qis
 */

namespace Qis\Tests;

use BaseTestCase;
use Qis\CommandInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Qis Command
 *
 * @uses CommandInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
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
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
    }
}

/**
 * Qis command interface test
 *
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@nerdery.com>
 * @version $Id$
 */
class CommandInterfaceTest extends BaseTestCase
{
    public $_qis;

    /**
     * Test get name
     *
     * @return void
     */
    public function testGetName()
    {
        $args     = new Qi_Console_ArgV(array());
        $terminal = new Qi_Console_Terminal();

        $this->_qis = new Qis($args, $terminal);

        $command = new Command($this->_qis, array());

        $name = $command->getName();
        $this->assertEquals('name', $name);
    }
}
