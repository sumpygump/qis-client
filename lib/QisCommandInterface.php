<?php 
/**
 * Qis Command Interface class file
 *
 * @package Qis
 */

// @codeCoverageIgnoreStart 

/**
 * Qis Command Interface
 * 
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
interface QisCommandInterface
{
    /**
     * Get name of this command object
     * 
     * @return string Name of object
     */
    public static function getName();

    /**
     * Constructor
     * 
     * @param Qis $qis Qis object
     * @param array $settings Settings
     * @return void
     */
    public function __construct(Qis $qis, $settings);

    /**
     * Initialize this command object
     * 
     * @return void
     */
    public function initialize();

    /**
     * Execute the logic of this command object
     * 
     * @param Qi_Console_ArgV $args Arguments
     * @return int
     */
    public function execute(Qi_Console_ArgV $args);

    /**
     * Get help message for this command object
     * 
     * @return string
     */
    public function getHelpMessage();
}

/**
 * QisCommandException
 *
 * @uses Exception
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class QisCommandException extends Exception
{
}
