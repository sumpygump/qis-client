<?php
/**
 * Modules command class file
 *
 * @package Qis
 */

namespace Qis\Command;

use Qis\CommandInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Tabular;

/**
 * Modules command class
 *
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Modules implements CommandInterface
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
        return 'modules';
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
        $modules = $this->_qis->getModules();

        $data = array();
        foreach ($modules as $command => $module) {
            $data[] = array(
                get_class($module),
                $command,
                $module->getHelpMessage(),
            );
        }

        $options = array(
            'headers' => array('Module', 'Command', 'Description'),
        );

        $table = new Qi_Console_Tabular($data, $options);
        $table->display();

        return 0;
    }

    /**
     * Get Help message for this module
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return "Show registered modules\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: modules\n"
            . "Shows a list of installed and enabled modules.\n";

        return $out;
    }
}
