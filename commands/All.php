<?php
/**
 * All command class file
 *
 * @package Qis
 */

/**
 * @see QisCommandInterface
 */
require_once 'QisCommandInterface.php';

/**
 * All command class (run all default modules)
 *
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qis_Command_All implements QisCommandInterface
{
    /**
     * Qis kernel object
     *
     * @var mixed
     */
    protected $_qis = null;

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
     * @param object $qis Qis object
     * @param mixed $settings Configuration settings
     * @return void
     */
    public function __construct(Qis $qis, $settings)
    {
        $this->_qis = $qis;
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
        $this->_executeAllModules($args);

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
     * Execute all modules with default action
     *
     * @param object $args Args
     * @return bool
     */
    protected function _executeAllModules($args)
    {
        $modules = $this->_qis->getModules();
        $order   = $this->_getBuildOrder();

        foreach (explode(',', $order) as $name) {
            $name = trim($name);
            if (!isset($modules[$name])) {
                continue;
            }
            $module = $modules[$name];

            $result = $module->execute($args);
            if ($result === 0) {
                $this->_qis->saveHistory(
                    $name, $module->getStatus(), $module->getSummary(true)
                );
            }
            $this->_rule();
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
    protected function _getBuildOrder()
    {
        $order = $this->_qis->getConfig()->get('build_order');

        $defaultBuildOrder = 'cs,test,coverage';

        if (empty($order) || !is_string($order)
            || trim($order) == ''
        ) {
            return $defaultBuildOrder;
        }

        return $order;
    }

    /**
     * Display a rule
     * 
     * @return void
     */
    protected function _rule()
    {
        echo "\n";
        $this->_qis->displayMessage(
            str_repeat('%', 80), true, 8, 4
        );
        echo "\n";
    }
}
