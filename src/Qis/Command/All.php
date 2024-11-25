<?php

/**
 * All command class file
 *
 * @package Qis
 */

namespace Qis\Command;

use Qis\CommandInterface;
use Qis\Qis;
use Qi_Console_ArgV;

/**
 * All command class (run all default modules)
 *
 * @uses \Qis\CommandInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class All implements CommandInterface
{
    /**
     * Qis kernel object
     *
     * @var Qis
     */
    protected $qis;

    /**
     * Get Name of command
     *
     * @return string
     */
    public static function getName()
    {
        return 'all';
    }

    /**
     * Constructor
     *
     * @param Qis $qis Qis object
     * @return void
     */
    public function __construct(Qis $qis)
    {
        $this->qis = $qis;
    }

    /**
     * Initialize this module after registration
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * Execute main logic
     *
     * @param Qi_Console_ArgV $args Arguments object
     * @return void
     */
    public function execute(Qi_Console_ArgV $args)
    {
        $this->executeAllModules($args);

        // TODO: Add some kind of metric to determine the overall health of
        // project

        return 0;
    }

    /**
     * Get Help message for this module
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return "Execute all modules\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: all\n"
            . "Runs all the modules in the order specified in .qis/config.ini\n\n"
            . "Default: build_order=cs,test,coverage\n";

        return $out;
    }

    /**
     * Execute all modules with default action
     *
     * @param object $args Args
     * @return bool
     */
    protected function executeAllModules($args)
    {
        $modules = $this->qis->getModules();
        $order   = $this->getBuildOrder();

        foreach (explode(',', $order) as $name) {
            $name = trim($name);
            if (!isset($modules[$name])) {
                continue;
            }
            $module = $modules[$name];

            $result = $module->execute($args);
            if ($result === 0) {
                $this->qis->saveHistory(
                    $name,
                    $module
                );
            }
            $this->rule();
        }

        return true;
    }

    /**
     * Get build order from config
     *
     * Default to sensible order
     *
     * @return string
     */
    protected function getBuildOrder()
    {
        $order = $this->qis->getConfig()->get('build_order');

        $defaultBuildOrder = 'cs,test,coverage';

        if (empty($order) || !is_string($order) || trim($order) == '') {
            return $defaultBuildOrder;
        }

        return $order;
    }

    /**
     * Display a rule
     *
     * @return void
     */
    protected function rule()
    {
        echo "\n";
        $this->qis->displayMessage(
            str_repeat('%', 80),
            true,
            8,
            4
        );
        echo "\n";
    }
}
