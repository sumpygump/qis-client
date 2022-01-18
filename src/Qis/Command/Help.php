<?php

/**
 * Help command class file
 *
 * @package Qis
 */

namespace Qis\Command;

use Qis\CommandInterface;
use Qis\CommandException;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Help command
 *
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Help implements CommandInterface
{
    /**
     * Qis kernel object
     *
     * @var mixed
     */
    protected $_qis = null;

    /**
     * _terminal
     *
     * @var Qi_Console_Terminal
     */
    protected $_terminal;

    /**
     * Get Name of command
     *
     * @return string
     */
    public static function getName()
    {
        return 'help';
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
        if ($args->__arg2) {
            $context = $args->__arg2;
        } else {
            $context = '.';
        }

        if ($context == '.') {
            echo $this->_showHelp();
        } else {
            echo $this->_showContextualHelp($context);
        }

        return 0;
    }

    /**
     * Get Help message for this module
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return "Show qis help information\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: help [command|module]\n"
            . "Without any arguments, display basic help.\n"
            . "This will display all the available modules\n"
            . "and commands.\n\n"
            . "Including a module or command name will provide\n"
            . "contextual help for that module or command.\n"
            . "Example: qis help coverage\n";

        return $out;
    }
    /**
     * Show help messages
     *
     * @return string
     */
    protected function _showHelp()
    {
        $out = '';

        $out .= $this->_terminal->do_setaf(2)
            . "Usage: qis <subcommand|module> [OPTIONS] [ARGS]\n\n";

        $out .= $this->_terminal->do_op()
            . "Subcommands:\n";

        $out .= $this->_terminal->do_setaf(3);

        foreach ($this->_qis->getCommands() as $name => $command) {
            $out .= '  ' . $name . ' : ' . $command->getHelpMessage();
        }

        if (count($this->_qis->getModules())) {
            $out .= $this->_terminal->do_op()
                . "\nModules:\n"
                . $this->_terminal->do_setaf(3);

            foreach ($this->_qis->getModules() as $name => $module) {
                $out .= '  ' . $name . ' : ' . $module->getHelpMessage();
            }
        }

        $out .= $this->_terminal->do_op();
        $out .= "\nUse `qis help [module|subcommand]` to get specific help\n"
            . "for a module or subcommand.\n";

        $out .= $this->getGlobalOptions();

        $out .= $this->_terminal->do_op();
        return $out;
    }

    /**
     * Get global options
     *
     * @return string
     */
    public function getGlobalOptions()
    {
        $out = $this->_terminal->do_op()
            . "\nGlobal Options:\n"
            . $this->_terminal->do_setaf(3)
            . "  -h [--help] : Show help\n"
            . "  -v [--verbose] : Show verbose messages\n"
            . "  -q [--quiet] : Print less messages\n"
            . "  --no-color : Don't use color output\n"
            . "  --version : Show version and exit\n";

        return $out;
    }

    /**
     * Contextual help
     *
     * @param string $context Module or subcommand name
     * @return string
     */
    protected function _showContextualHelp($context)
    {
        $contextType = 'module';
        $out = '';

        // First try to find module by name
        $contextObject = $this->_qis->getModule($context);

        if (!$contextObject) {
            // No module; try a command by that name
            $contextObject = $this->_qis->getCommand($context);
            $contextType = 'command';
        }

        if (!$contextObject) {
            throw new CommandException("No module or command by name '$context' found.", 64);
        }

        $this->_qis->prettyMessage("Help for $contextType '$context'", 8, 4);
        $out .= $contextObject->getExtendedHelpMessage();

        $out .= $this->getGlobalOptions();
        return $out;
    }
}
