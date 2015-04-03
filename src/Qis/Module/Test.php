<?php
/**
 * Test module class file 
 *
 * @package Qis
 */

namespace Qis\Module;

use Qis\ModuleInterface;
use Qis\Qis;
use Qi_Console_Tabular;
use Qi_Console_ArgV;
use Exception;

/**
 * Test runner module
 * 
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Test implements ModuleInterface
{
    /**
     * Output path
     * 
     * @var string
     */
    protected $_outputPath = 'test-results';

    /**
     * Qis kernel object
     * 
     * @var mixed
     */
    protected $_qis = null;

    /**
     * Settings
     * 
     * @var array
     */
    protected $_settings = array();

    /**
     * Path (root of tests to run)
     *
     * This can be overridden in the config
     * For example if you want to define it
     * in the phpunit configuration xml
     * 
     * @var string
     */
    protected $_path = '.';

    /**
     * Constructor
     * 
     * @param Qis $qis Qis object
     * @param array $settings Config settings
     * @return void
     */
    public function __construct(Qis $qis, $settings)
    {
        $this->_qis      = $qis;
        $this->_settings = $settings;

        // Store the test run path
        if (isset($settings['path'])) {
            $this->_path = $settings['path'];
        }
    }

    /**
     * Initialize this module after registration
     * 
     * @return void
     */
    public function initialize()
    {
        $this->_outputPath = $this->_qis->getProjectQisRoot()
            . DIRECTORY_SEPARATOR
            . $this->_outputPath . DIRECTORY_SEPARATOR;

        if (!file_exists($this->_outputPath)) {
            mkdir($this->_outputPath);
        }
    }

    /**
     * Execute main logic
     * 
     * @param Qi_Console_ArgV $args Arguments
     * @return int
     */
    public function execute(Qi_Console_ArgV $args)
    {
        $this->_qis->qecho("\nRunning Test (unit tests) module task...\n");

        if ($args->__arg2) {
            $path = $args->__arg2;
        } else {
            $path = '.';
        }

        if ($args->list) {
            return $this->showList();
        }

        $options = array();

        if ($args->tap) {
            $options['tap'] = true;
        }

        $this->_saveTimeStamp();
        $this->runTest($path, $options);

        $this->_qis->qecho("\nCompleted Test module task.\n");
        return 0;
    }

    /**
     * Help message
     * 
     * @return string
     */
    public function getHelpMessage()
    {
        return "Run unit tests for project\n";
    }

    /**
     * Get extended help message
     * 
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: test [OPTIONS] [path]\n"
            . "By default this will run all tests in the configured tests "
            . "directory.\n"
            . "You can specify a path to run tests "
            . "for a certain file or directory.\n";

        $out .= "\nValid Options:\n"
            . $this->_qis->getTerminal()->do_setaf(3)
            . "  --list : Show list of previous tests run\n"
            . "  --tap  : Run tests using TAP output format\n"
            . $this->_qis->getTerminal()->do_op();

        return $out;
    }

    /**
     * Get summary of this module
     * 
     * @param bool $short Get short summary
     * @param string $label Label
     * @return string
     */
    public function getSummary($short = false, $label = null)
    {
        if ($short) {
            return $this->_getShortSummary($label);
        }

        return $this->displaySummary(false);
    }

    /**
     * Get short summary
     * 
     * @param string $label Label
     * @return string
     */
    protected function _getShortSummary($label = null)
    {
        if (null === $label) {
            $label = 'Test: ';
        }

        if ($this->getStatus()) {
            $result = 'PASS';
        } else {
            $result = 'FAIL';
        }

        return $label . $result;
    }

    /**
     * Get status for this module (pass/fail)
     * 
     * @return bool
     */
    public function getStatus()
    {
        $metrics = $this->getMetrics();
        if ($metrics === false) {
            // This means the data isn't available yet
            return false;
        }

        $sum = $metrics['failures'] + $metrics['errors'];
        return (!$sum);
    }

    /**
     * Run test
     * 
     * @param string $path Path to run tests
     * @param array $options Array of options
     * @return void
     */
    public function runTest($path = '.', $options = array())
    {
        $coverageReportFilename = $this->_outputPath
            . 'coverage.xml';

        $coverageHtmlDir = $this->_outputPath
            . 'coverage' . DIRECTORY_SEPARATOR;

        $testsDir = $this->_path;

        if (!file_exists($testsDir)) {
            $this->_qis->halt("Tests directory '$testsDir' not found.");
        }

        $colors = '';
        if ($this->_qis->getTerminal()->isatty()) {
            $colors = '--colors ';
        }

        $bootstrap = '';
        if (isset($this->_settings['bootstrap'])
            && $this->_settings['bootstrap']
        ) {
            $bootstrap = '--bootstrap=' . $this->_settings['bootstrap'] . ' ';
        }

        $configuration = '';
        if (isset($this->_settings['configuration'])
            && $this->_settings['configuration']
        ) {
            $configuration = '--configuration='
                . $this->_settings['configuration'] . ' ';

            // Set path to empty if it is not set to something other than . to 
            // allow for setting a more specific path in the XML configuration 
            // file
            if ($path == '.') {
                $path = '';
            }
        } else {
            if ($bootstrap == '') {
                // if no configuration setting and no bootstrap defined, let's 
                // check if there is a bootstrap file and auto bootstrap it.
                $detectedBootstrapFile = $testsDir . DIRECTORY_SEPARATOR . 'bootstrap.php';
                if (file_exists($detectedBootstrapFile)) {
                    $this->_qis->qecho("Auto-detected bootstrap file bootstrap.php\n");
                    $bootstrap = '--bootstrap=bootstrap.php ';
                }
            }
        }

        $executionOutputFormat = '';
        if (isset($options['tap']) && $options['tap']) {
            $executionOutputFormat = '--tap ';
        }

        $phpunitBin = $this->_qis->getConfig()->get('phpunit_bin');

        // If phpunit binary path is not in config file, default to 'phpunit'
        $isEmptyPhpunitBin = (array) $phpunitBin;
        if (empty($isEmptyPhpunitBin)) {
            $phpunitBin = 'phpunit';
        }

        $cmd = 'cd ' . $testsDir . ';'
            . $phpunitBin . ' '
            . $bootstrap
            . $configuration
            . $colors
            . $executionOutputFormat
            . '--log-junit ' . $this->_outputPath . 'log.junit '
            . '--log-tap ' . $this->_outputPath . 'log.tap '
            // Dont log json: it breaks when testing binary values in 
            // assertions in phpunit < 3.7
            //. '--log-json ' . $this->_outputPath . 'log.json '
            //. '--story-text ' . $this->_outputPath . 'story.text.txt '
            . '--testdox-text ' . $this->_outputPath . 'testdox.text.txt '
            . '--coverage-clover=' . $coverageReportFilename . ' '
            . '--coverage-html=' . $coverageHtmlDir . ' '
            . $path
            . ' | tee ' . $this->_outputPath . 'output.log;'
            . 'cd - > /dev/null';

        $this->_qis->log($cmd);

        passthru($cmd);
    }

    /**
     * Save timestamp
     * 
     * @return void
     */
    protected function _saveTimeStamp()
    {
        $file     = $this->_outputPath . 'lastrun';
        $contents = date('Y-m-d H:i:s');

        return file_put_contents($file, $contents);
    }

    /**
     * Get timestamp from last run
     * 
     * @return string
     */
    public function getLastRunTimeStamp()
    {
        $file = $this->_outputPath . 'lastrun';

        return trim(file_get_contents($file));
    }

    /**
     * Get default ini settings for this module
     * 
     * @return string
     */
    public static function getDefaultIni()
    {
        return "; Run unit and integration tests for a project\n"
            . "test.command=test\n"
            . "test.class=" . get_called_class() . "\n"
            . "test.bootstrap=\n"
            . "test.configuration=\n"
            . "test.path=tests\n"
            ;
    }

    /**
     * Display summary
     * 
     * @param bool $pretty Use pretty output
     * @return mixed
     */
    public function displaySummary($pretty = true)
    {
        $results = $this->getMetrics();
        if (false === $results) {
            return 'No data yet.';
        }

        $table = new Qi_Console_Tabular(
            array(array_values($results)),
            array('headers' => array_keys($results))
        );

        $fg = 8;
        $bg = 4;

        $out = "Test (unit tests) results:\n" . $table->display(true);
        if ($pretty) {
            $this->_qis->prettyMessage(trim($out), $fg, $bg);
        } else {
            return $out;
        }
    }

    /**
     * Get metrics for current results
     * 
     * @param bool $onlyPrimary Return only the primary metric
     * @return array|float
     */
    public function getMetrics($onlyPrimary = false)
    {
        $metrics = $this->readLogJunit($this->_outputPath . 'log.junit');

        if (!$onlyPrimary) {
            return $metrics;
        }

        if (false === $metrics || !isset($metrics['tests'])) {
            return 0.0;
        }

        // Primary metric is number of passing tests
        return $metrics['tests'] - ($metrics['failures'] + $metrics['errors']);
    }

    /**
     * Show list of last results
     * 
     * @return bool
     */
    public function showList()
    {
        echo "Last run results:\n";
        echo $this->getLastRunTimeStamp() . " \n";
        echo str_repeat('-', 32) . "\n";

        $data = $this->readLogTap($this->_outputPath . 'log.tap');

        echo $data;

        return ModuleInterface::RETURN_BENIGN;
    }

    /**
     * Read log from junit (xml) format
     * 
     * @param string $filename Filename
     * @return array
     */
    public function readLogJunit($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }

        libxml_use_internal_errors(true);
        $data = simplexml_load_file($filename);
        if (false == $data) {
            libxml_clear_errors();
            return false;
        }

        $suite = $data->testsuite;

        $results = array(
            'tests'      => (string) $suite['tests'],
            'assertions' => (string) $suite['assertions'],
            'failures'   => (string) $suite['failures'],
            'errors'     => (string) $suite['errors'],
        );

        return $results;
    }

    /**
     * Read the tap log file
     * 
     * @param string $filename Filename
     * @return string
     */
    public function readLogTap($filename)
    {
        $data = file_get_contents($filename);
        return $data;
    }
}
