<?php
/**
 * Qis Module Interface class file
 *
 * @package Qis
 */

/**
 * Qis Module Interface
 * 
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
interface QisModuleInterface
{
    /**
     * Get the default ini for this module
     *
     * @return string
     */
    public static function getDefaultIni();

    /**
     * Constructor
     *
     * @param Qis $qis Qis object
     * @param array $settings Configuration settings
     * @return void
     */
    public function __construct(Qis $qis, $settings);

    /**
     * Initialize this module
     *
     * @return void
     */
    public function initialize();

    /**
     * Execute main logic for this module
     *
     * @param Qi_Console_ArgV $args Arguments
     * @return int
     */
    public function execute(Qi_Console_ArgV $args);

    /**
     * Get help message for this module
     *
     * @return string
     */
    public function getHelpMessage();

    /**
     * Get extended help message for this module
     *
     * @return string
     */
    public function getExtendedHelpMessage();

    /**
     * Get status of this module
     *
     * @return string
     */
    public function getStatus();

    /**
     * Get summary for this module
     *
     * @param bool $short Return short message
     * @return string
     */
    public function getSummary($short = false);

    /**
     * Get metrics for current status
     *
     * @return void
     */
    public function getMetrics();
}
