<?php
/**
 * History command class file
 *
 * @package Qis
 */

namespace Qis\Command;

use Qis\CommandInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Tabular;

/**
 * History Command
 *
 * @uses CommandInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class History implements CommandInterface
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
        return 'history';
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
        $this->_qis      = $qis;
        $this->_terminal = $this->_qis->getTerminal();
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
        $history = $this->_qis->readHistory();

        if ($args->__arg2) {
            $targetModule = $args->__arg2;
            printf("Module filter: %s\n", $targetModule);

            $filteredHistory = array();
            foreach ($history as $item) {
                if ($item->module == $targetModule) {
                    $filteredHistory[] = $item;
                }
            }
            $history = $filteredHistory;
        } else {
            $targetModule = false;
        }

        if (count($history) == 0) {
            print "No history to display.\n";
            return 0;
        }

        $headers = array(
            'Date', 'Module', 'Status', 'Summary', 'Metric',
        );

        $rows = array();

        foreach ($history as $item) {
            $row = array(
                'date'    => $item->date,
                'module'  => $item->module,
                'status'  => $item->status ? 'PASS' : 'FAIL',
                'summary' => $item->summary,
                'metric'  => $item->metric,
            );

            $rows[] = $row;
        }

        $table = new Qi_Console_Tabular(
            $rows,
            array('headers' => $headers)
        );

        $table->display();

        return 0;
    }

    /**
     * Get Help message for this command
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return "Show history data for modules\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: history [module]\n"
            . "This will display a history of the results for modules that\n"
            . "have been run previously, including the pass/fail status and\n"
            . "basic metric. This basic metric differs per module.\n"
            . "With an argument provided, it will filter the results\n"
            . "to only show that module.\n";

        return $out;
    }
}
