<?php
/**
 * Qis class file
 *
 * @package Qis
 */

namespace Qis;

use Qi_Console_ArgV;

/**
 * Qis: Quantal Integration System
 *
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qis
{
    /**
     * Version
     *
     * @var string
     */
    const VERSION = '1.2.3';

    /**
     * Configuration
     *
     * @var mixed
     */
    protected $_config = null;

    /**
     * Terminal The terminal object
     *
     * @var object
     */
    protected $_terminal;

    /**
     * ArgV The arguments object
     *
     * @var object
     */
    protected $_args;

    /**
     * Whether verbose output
     *
     * @var bool
     */
    protected $_verbose = false;

    /**
     * Commands (Qis subcommands)
     *
     * @var array
     */
    protected $_commands = array();

    /**
     * Modules
     *
     * @var array
     */
    protected $_modules = array();

    /**
     * Qis root
     *
     * @var string
     */
    protected $_projectQisRoot = '';

    /**
     * Whether this script should allow exiting
     *
     * @var bool
     */
    public static $exit = true;

    /**
     * Constructor
     *
     * @param object $args ArgV object
     * @param object $terminal Terminal object
     * @return void
     */
    public function __construct(Qi_Console_ArgV $args, $terminal)
    {
        $this->_args     = $args;
        $this->_terminal = $terminal;

        $this->_projectQisRoot = realpath('.') . DIRECTORY_SEPARATOR . '.qis';
    }

    /**
     * Get terminal object
     *
     * @return object Terminal
     */
    public function getTerminal()
    {
        return $this->_terminal;
    }

    /**
     * Get configuration
     *
     * @return object
     */
    public function getConfig()
    {
        if (!$this->_config) {
            $this->_config = new Config();
        }
        return $this->_config;
    }

    /**
     * Set config object
     *
     * @param Config $config Config object
     * @return object
     */
    public function setConfig(Config $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Get Project Qis Root path
     *
     * @return string
     */
    public function getProjectQisRoot()
    {
        return $this->_projectQisRoot;
    }

    /**
     * Get Qis Version
     *
     * @return string
     */
    public function getVersion()
    {
        return self::VERSION;
    }

    /**
     * Whether verbose mode is on
     *
     * @return bool
     */
    public function isVerbose()
    {
        return $this->_verbose;
    }

    /**
     * Register internal commands
     *
     * @return void
     */
    protected function _registerCommands()
    {
        $files = glob(
            dirname(__FILE__) . DIRECTORY_SEPARATOR
            . "Command" . DIRECTORY_SEPARATOR . "*.php"
        );

        foreach ($files as $file) {
            include_once $file;
            $classname   = 'Qis\\Command\\' . pathinfo($file, PATHINFO_FILENAME);
            $commandName = call_user_func(array($classname, 'getName'));

            $this->_commands[$commandName] = new $classname($this, array());
        }
    }

    /**
     * Register modules
     *
     * @param array $modules Modules config array
     * @return void
     */
    public function registerModules($modules)
    {
        if (!is_array($modules) && !is_object($modules)) {
            $this->log('Cannot load modules due to incorrect input.');
            return false;
        }

        foreach ($modules as $name => $settings) {
            $this->registerModule($name, $settings);
        }

        return count($this->_modules);
    }

    /**
     * Register module
     *
     * @param mixed $name Name to register
     * @param mixed $settings Settings for module
     * @return void
     */
    public function registerModule($name, $settings)
    {
        $filename = ucfirst(preg_replace('/[^A-Za-z_]/', '', $name));

        if (isset($settings['class']) && trim($settings['class']) != '') {
            $className = $settings['class'];
        } else {
            $className = 'Qis\\Module\\' . $filename;
        }

        if (isset($settings['command'])) {
            $command = strtolower($settings['command']);
        } else {
            $command = strtolower($name);
        }

        // If it doesn't exist, fail; Should be autoloaded already
        if (!class_exists($className)) {
            $this->warningMessage(
                "Failed to load module $name. Class $className not found."
            );
            return false;
        }

        // Instantiate module class
        try {
            $module = new $className($this, $settings);
            $module->initialize();
        } catch (Exception $e) {
            $this->warningMessage(
                "Failed to load module $name. "
                . "Error message: " . $e->getMessage()
                . ", File:" . $e->getFile() . ':' . $e->getLine()
            );
            return false;
        }

        $this->log("Loaded module $name.");

        $this->_modules[$command] = $module;
    }

    protected function getAction()
    {
        $action = trim($this->_args->action);

        if ($action != '') {
            return $action;
        }

        return 'summary';
    }

    protected function preinit()
    {
        if ($this->_args->v) {
            $this->_verbose = true;
        }

        $this->_registerCommands();
        $this->_loadProjectConfig();
        if ($this->_config) {
            $this->registerModules($this->_config->modules);
        }
    }

    /**
     * Execute main logic
     *
     * @return int
     */
    public function execute()
    {
        $this->preinit();

        // Detect '--help' and exit
        if ($this->_args->help) {
            $this->_showHelp();
            return 0;
        }

        // Detect '--version' and exit
        if ($this->_args->version) {
            echo $this->renderTitle();
            return 0;
        }

        $action = $this->getAction();
        if (!$this->_config && $action != 'init') {
            $this->displayError(
                "No project config file found. Use 'qis init' to initialize."
            );
            //return 0;
        } else {
            $this->qecho($this->renderTitle());
            if ($this->_config) {
                $this->qecho("Project: " . $this->_config->project_name . "\n");
            }
        }

        // Find command to run
        if (isset($this->_commands[$action])) {
            $command = $this->_commands[$action];
            return $command->execute($this->_args);
        }

        // Find module to run
        if (isset($this->_modules[$action])) {
            $module = $this->_modules[$action];

            $returnCode = $module->execute($this->_args);
            if ($returnCode === 0) {
                $this->saveHistory(
                    $action,
                    $module
                );
            }
            return $returnCode;
        } else {
            $this->halt("Unrecognized command '$action'", 1);
        }
    }

    /**
     * Load project configuration
     *
     * @return void
     */
    protected function _loadProjectConfig()
    {
        $file = $this->getProjectQisRoot()
            . DIRECTORY_SEPARATOR . "config.ini";

        if (!file_exists($file)) {
            return false;
        }

        $config = new Config($file);

        $this->setConfig($config);
    }

    /**
     * Get registered modules
     *
     * @return array An array of module objects
     */
    public function getModules()
    {
        return $this->_modules;
    }

    /**
     * Get module by name
     *
     * @param string $name Name of module
     * @return object
     */
    public function getModule($name)
    {
        if (!isset($this->_modules[$name])) {
            return false;
        }

        return $this->_modules[$name];
    }

    /**
     * Get commands
     *
     * @return array An array of command objects
     */
    public function getCommands()
    {
        return $this->_commands;
    }

    /**
     * Get command by name
     *
     * @param string $name Name of command
     * @return QisCommandInterface|false
     */
    public function getCommand($name)
    {
        if (!isset($this->_commands[$name])) {
            return false;
        }

        return $this->_commands[$name];
    }

    /**
     * Get history file path
     *
     * @return string
     */
    public function getHistoryFilepath()
    {
        return $this->getProjectQisRoot()
            . DIRECTORY_SEPARATOR . 'history.json';
    }

    /**
     * Save history
     *
     * @param string $moduleName Module name
     * @param QisModuleInterface $module Module
     * @return void
     */
    public function saveHistory($moduleName, $module)
    {
        $history = $this->readHistory();

        $index = date('Y-m-d H:i:s');

        $history[] = array(
            'module'  => $moduleName,
            'date'    => $index,
            'status'  => $module->getStatus(),
            'summary' => $module->getSummary(true),
            'metric'  => json_encode($module->getMetrics(true)),
            'metrics' => json_encode($module->getMetrics()),
        );

        file_put_contents(
            $this->getHistoryFilepath(), json_encode($history)
        );
    }

    /**
     * Read history data
     *
     * @return array
     */
    public function readHistory()
    {
        $historyFile = $this->getHistoryFilepath();

        if (!file_exists($historyFile)) {
            $history = array();
        } else {
            $history = file_get_contents($historyFile);
            $history = json_decode($history);
        }

        return $history;
    }

    /**
     * Log message
     *
     * @param string $message Message
     * @return void
     */
    public function log($message)
    {
        if ($this->_verbose) {
            $out = $this->_terminal->do_setaf(3)
                . ">> " . $message . "\n"
                . $this->_terminal->do_op();
            echo $out;
        }
    }

    /**
     * Show help message
     *
     * @return void
     */
    protected function _showHelp()
    {
        echo $this->renderTitle();

        if ($this->_config && !empty($this->_config->project_name)) {
            echo "Project: " . $this->_config->project_name . "\n";
        }

        $this->_commands['help']->execute($this->_args);
    }

    /**
     * Show program title
     *
     * @return string
     */
    public function renderTitle()
    {
        $out = $this->_terminal->do_setaf(2)
            . "Quantal Integration System " . $this->getVersion() . "\n"
            . $this->_terminal->do_op();

        return $out;
    }

    /**
     * Exit with error message
     *
     * @param mixed $message Message
     * @param int $status Exit status to send
     * @return void
     */
    public function halt($message, $status = 2)
    {
        $this->displayError($message);

        if (self::$exit) {
            exit($status);
        }

        return 1;
    }

    /**
     * Echo text but only if --quiet is not an argument
     *
     * @param string $text Text to echo
     * @return mixed
     */
    public function qecho($text)
    {
        if ($this->_args->get('quiet')) {
            return;
        }
        echo $text;
    }

    /**
     * Display a warning message
     *
     * @param string $message Message
     * @param bool $ensureNewline Whether to ensure a newline at end of message
     * @return void
     */
    public function warningMessage($message, $ensureNewline = true)
    {
        $this->displayMessage($message, $ensureNewline, 1); //red
    }

    /**
     * Display an error message
     *
     * @param string $message Message text
     * @return void
     */
    public function displayError($message)
    {
        echo "\n";
        $this->_terminal->pretty_message($message, 7, 1);
        echo "\n";
    }

    /**
     * Display a message
     *
     * @param mixed $message Message text
     * @param bool $ensureNewline Include a new line at end
     * @param int $color Foreground color
     * @param int $background Background color
     * @return void
     */
    public function displayMessage($message,
        $ensureNewline = true, $color = 2, $background = 0)
    {

        $this->_terminal->setaf($color);
        $this->_terminal->setab($background);
        echo $message;
        $this->_terminal->op();

        if ($ensureNewline && substr($message, -1) != "\n") {
            //$message .= "\n";
            echo "\n";
        }
    }

    /**
     * Display pretty message
     *
     * @param string $message Message text
     * @param int $fg Foreground color (1-7)
     * @param int $bg Background color (1-7)
     * @return void
     */
    public function prettyMessage($message, $fg, $bg)
    {
        echo "\n";

        if ($this->_terminal->isatty()) {
            $this->_terminal->pretty_message($message, $fg, $bg);
        } else {
            echo $message;
        }

        echo "\n";
    }
}
