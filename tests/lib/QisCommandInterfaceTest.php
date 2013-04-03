<?php
/**
 * Test qis command interface
 *
 * @package Qis
 */

require_once 'QisCommandInterface.php';

/**
 * Qis Command
 *
 * @uses QisCommandInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class QisCommand implements QisCommandInterface
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
 * @covers QisCommandInterface
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@nerdery.com>
 * @version $Id$
 */
class QisCommandInterfaceTest extends BaseTestCase
{
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

        $command = new QisCommand($this->_qis, array());

        $name = $command->getName();
    }
}
