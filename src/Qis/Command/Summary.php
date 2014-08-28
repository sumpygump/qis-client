<?php
/**
 * Summary command class file
 *
 * @package Qis
 */

namespace Qis\Command;

use Qis\CommandInterface;
use Qis\Qis;
use Qi_Console_ArgV;

/**
 * Summary command class
 *
 * @uses QisCommandInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Summary implements CommandInterface
{
    /**
     * Qis kernel object
     *
     * @var mixed
     */
    protected $_qis = null;

    /**
     * Don't use color output
     *
     * @var mixed
     */
    protected $_noColor = false;

    /**
     * Get Name of command
     *
     * @return string
     */
    public static function getName()
    {
        return 'summary';
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
     * @param Qi_Console_ArgV $args Arguments
     * @return void
     */
    public function execute(Qi_Console_ArgV $args)
    {
        if ($args->__arg2) {
            $module = $args->__arg2;
        } else {
            $module = 'all';
        }

        if ($args->get('no-color')) {
            $this->_noColor = true;
        }

        $shortSummary = false;
        if ($args->get('short')) {
            $shortSummary = true;
            echo str_repeat('-', 32) . "\n";
        }

        if ($module == 'all') {
            $modules = $this->_qis->getModules();
        } else {
            $modules = array($this->_qis->getModule($module));
        }

        foreach ($modules as $command => $module) {
            $summary = $module->getSummary($shortSummary);

            if ($shortSummary) {
                if ($this->_noColor) {
                    echo $summary . "\n";
                } else {
                    $this->_displayStatusMessage(
                        $summary, $module->getStatus()
                    );
                }
            } else {
                if ($this->_noColor) {
                    echo "\n" . $summary. "\n"
                        . $module->getSummary(true) . "\n";
                } else {
                    $fg = 8;
                    $bg = 4;
                    $this->_qis->prettyMessage(trim($summary), $fg, $bg);
                    $this->_displayStatusMessage(
                        $module->getSummary(true),
                        $module->getStatus()
                    );
                }
            }
        }

        echo "\n";

        return 0;
    }

    /**
     * Display status message
     *
     * Display a message colored either positive or negative
     *
     * @param string $message Message
     * @param bool $status Positive (true) or negative (false)
     * @return void
     */
    protected function _displayStatusMessage($message, $status)
    {
        if ($status) {
            $fg = 0;
            $bg = 2;
        } else {
            $fg = 8;
            $bg = 1;
        }

        $this->_qis->displayMessage($message, true, $fg, $bg);
    }

    /**
     * Get Help message for this module
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return "Get summary of a module or all modules\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: summary [--short] [module]\n"
            . "Show the summary of the most recent results of each module.\n"
            . "If a module name is provided as an argument, it\n"
            . "will display only the summary for that module.\n\n"
            . "This is the default module that is run when no\n"
            . "module name is given when running qis.\n";

        $out .= "\nValid Options:\n"
            . $this->_qis->getTerminal()->do_setaf(3)
            . "  --short : Show only short information\n"
            . $this->_qis->getTerminal()->do_op();

        return $out;
    }
}
