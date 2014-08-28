<?php
/**
 * Init command class file
 *
 * @package Qis
 */

namespace Qis\Command;

use Qis\CommandInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Std;

/**
 * Init command class
 * 
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Init implements CommandInterface
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
        return 'init';
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
        return $this->_initializeProject();
    }

    /**
     * Get Help message for this module
     * 
     * @return string
     */
    public function getHelpMessage()
    {
        return "Initialize a project in the current folder\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: init\n"
            . "This will initialize a qis project in the current directory.\n"
            . "If one already exists, it will prompt to overwrite it.\n"
            . "Initializing a qis project will prompt the user for some\n"
            . "basic information about the project.\n"
            . "The files will be written to a .qis directory.\n";

        return $out;
    }
    /**
     * Initialize Project
     * 
     * @return int
     */
    protected function _initializeProject()
    {
        echo "Initializing project...";

        $path = $this->_qis->getProjectQisRoot();

        if ($this->_verifyDirExists($path)) {
            if (!$this->_promptOverwrite()) {
                return 0;
            } else {
                passthru("rm -rfv \"$path\"");
                mkdir($path);
            }
        }

        $this->_verifyDirExists($path, true);

        $file = $path . DIRECTORY_SEPARATOR . 'config.ini';

        $projectName = $this->_promptProjectName();

        $contents = '; QIS configuration file'
            . ' v' . $this->_qis->getVersion() . "\n"
            . "project_name=$projectName\n"
            . "project_root=\"" . realpath(dirname('.')) . "\"\n"
            . "\nphpunit_bin=\"phpunit\"\n"
            . "\nbuild_order=cs,test,coverage\n";

        $contents .= $this->_addModuleConfigDefaults();

        file_put_contents($file, $contents);

        echo "done.\n";

        return 0;
    }

    /**
     * Prompt for a project name
     * 
     * @return void
     */
    protected function _promptProjectName()
    {
        $name = '';

        if (!$this->_qis->getTerminal()->isatty()) {
            return $name;
        }

        echo "\nEnter project name: ";
        $name = Qi_Console_Std::in();

        return trim($name);
    }

    /**
     * Prompt to overwrite
     * 
     * @return bool
     */
    protected function _promptOverwrite()
    {
        $this->_qis->warningMessage(
            "\nQis has already been initialized for this project."
        );
        echo "Do you want to re-init [All data will be lost] (y/n)? ";
        $response = Qi_Console_Std::in();

        if (strtolower(trim($response)) == 'y') {
            return true;
        }

        return false;
    }

    /**
     * Find module classes and get default config ini
     * 
     * @return string
     */
    protected function _addModuleConfigDefaults()
    {
        $contents = "\n[modules]\n";

        echo "\n";

        $modulesDir = dirname(dirname(__FILE__))
            . DIRECTORY_SEPARATOR . "Module";

        printf("Modules dir: %s\n", $modulesDir);

        // Get internal modules by searching the modules directory for php files
        $files = glob($modulesDir . DIRECTORY_SEPARATOR . "*.php");

        foreach ($files as $file) {
            include_once $file;
            $classname = 'Qis\\Module\\' . pathinfo($file, PATHINFO_FILENAME);
            echo "  Initializing " . $classname . "\n";
            $contents .= call_user_func(
                array($classname, 'getDefaultIni')
            ) . "\n";
        }

        return $contents;
    }

    /**
     * Check existence of directory
     * 
     * @param string $dir Directory to check
     * @param bool $create Whether to create if not exists
     * @return bool
     */
    protected function _verifyDirExists($dir, $create = false)
    {
        $this->_qis->log("Checking existence of directory '$dir'");

        if (!is_dir($dir)) {
            $this->_qis->log("Directory '$dir' doesn't exist.");
            if ($create) {
                $this->_qis->log("Creating directory '$dir'.");
                mkdir($dir);
            }
            return false;
        }

        $this->_qis->log("Directory '$dir' found.");

        return true;
    }

}
