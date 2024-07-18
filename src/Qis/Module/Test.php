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
    protected $outputPath = 'test-results';

    /**
     * Qis kernel object
     *
     * @var Qis
     */
    protected $qis = null;

    /**
     * Settings
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Path (root of tests to run)
     *
     * This can be overridden in the config
     * For example if you want to define it
     * in the phpunit configuration xml
     *
     * @var string
     */
    protected $path = '.';

    /**
     * Args from last execution
     *
     * @var array<string, string>
     */
    protected $args = [];

    /**
     * Constructor
     *
     * @param Qis $qis Qis object
     * @param array $settings Config settings
     * @return void
     */
    public function __construct(Qis $qis, $settings)
    {
        $this->qis      = $qis;
        $this->settings = $settings;

        // Store the test run path
        if (isset($settings['path'])) {
            $this->path = $settings['path'];
        }
    }

    /**
     * Initialize this module after registration
     *
     * @return void
     */
    public function initialize()
    {
        $this->outputPath = $this->qis->getProjectQisRoot()
            . DIRECTORY_SEPARATOR
            . $this->outputPath . DIRECTORY_SEPARATOR;

        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath);
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
        $this->args = $args->toArray();
        $this->qis->qecho("\nRunning Test (unit tests) module task...\n");

        if ($args->__arg2) {
            $path = $args->__arg2;
        } else {
            $path = '.';
        }

        if ($args->init) {
            // Special case to initialize testing environment
            return $this->initializeTestEnvironment();
        }

        if ($args->list) {
            return $this->showList();
        }

        $options = [];

        if ($args->testdox) {
            $options['testdox'] = true;
        }

        $this->saveTimeStamp();
        $this->runTest($path, $options);

        $this->qis->qecho("\nCompleted Test module task.\n");
        return 0;
    }

    /**
     * Get args from last invocation
     *
     * @return string
     */
    public function getArgs()
    {
        return $this->args['__arg2'] ?? '';
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
            . $this->qis->getTerminal()->do_setaf(3)
            . "  --init : Initialize a test environment\n"
            . "  --list : Show list of previous tests run\n"
            . "  --testdox : Use testdox output when running tests\n"
            . $this->qis->getTerminal()->do_op();

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
            return $this->getShortSummary($label);
        }

        return $this->displaySummary(false);
    }

    /**
     * Get short summary
     *
     * @param string $label Label
     * @return string
     */
    protected function getShortSummary($label = null)
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
    public function runTest($path = '.', $options = [])
    {
        $coverageReportFilename = $this->outputPath
            . 'coverage.xml';

        $coverageHtmlDir = $this->outputPath
            . 'coverage' . DIRECTORY_SEPARATOR;

        $testsDir = $this->path;

        $rootTestsDir = false;
        if ($testsDir == '') {
            $testsDir = getcwd();
            $rootTestsDir = true;
            if ($path == '.') {
                $path = '';
            }
        } else {
            if (!file_exists($testsDir)) {
                $this->qis->halt("Tests directory '$testsDir' not found.");
            }
        }

        $colors = '';
        if ($this->qis->getTerminal()->isatty()) {
            $colors = '--colors ';
        }

        $bootstrap = '';
        if (isset($this->settings['bootstrap']) && $this->settings['bootstrap']) {
            $bootstrap = '--bootstrap=' . $this->settings['bootstrap'] . ' ';
        }

        $configuration = '';
        if (
            isset($this->settings['configuration'])
            && $this->settings['configuration']
        ) {
            $configuration = '--configuration='
                . $this->settings['configuration'] . ' ';

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
                    $this->qis->qecho("QIS Auto-detected bootstrap file bootstrap.php\n");
                    $bootstrap = '--bootstrap=bootstrap.php ';
                }
            }
        }

        $executionOutputFormat = '';
        if (isset($options['testdox']) && $options['testdox']) {
            $executionOutputFormat = '--testdox ';
        }

        $phpunitBin = $this->settings['bin'];

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
            . '--log-junit ' . $this->outputPath . 'log.junit '
            . '--testdox-text ' . $this->outputPath . 'testdox.text.txt '
            . '--coverage-clover=' . $coverageReportFilename . ' ';

        if (isset($this->settings['coverage-html']) && (bool) $this->settings['coverage-html']) {
            $cmd .= '--coverage-html=' . $coverageHtmlDir . ' ';
        }

        $cmd .= $path
            . ' | tee ' . $this->outputPath . 'output.log;'
            . 'cd - > /dev/null';

        $this->qis->log($cmd);

        passthru($cmd);
    }

    /**
     * Save timestamp
     *
     * @return void
     */
    protected function saveTimeStamp()
    {
        $file     = $this->outputPath . 'lastrun';
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
        $file = $this->outputPath . 'lastrun';

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
            . "test.bin=../vendor/bin/phpunit\n"
            . "test.bootstrap=\n"
            . "test.configuration=\n"
            . "test.coverage-html=true\n"
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
            [array_values($results)],
            ['headers' => array_keys($results)]
        );

        $fg = 8;
        $bg = 4;

        $out = "Test (unit tests) results:\n" . $table->display(true);
        if ($pretty) {
            $this->qis->prettyMessage(trim($out), $fg, $bg);
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
        $metrics = $this->readLogJunit($this->outputPath . 'log.junit');

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

        $data = $this->readTestDox($this->outputPath . 'testdox.text.txt');

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

        $results = [
            'tests'      => (string) $suite['tests'],
            'assertions' => (string) $suite['assertions'],
            'failures'   => (string) $suite['failures'],
            'errors'     => (string) $suite['errors'],
        ];

        return $results;
    }

    /**
     * Read the testdox text file
     *
     * @param string $filename Filename
     * @return string
     */
    public function readTestDox($filename)
    {
        $data = file_get_contents($filename);
        return $data;
    }

    public function initializeTestEnvironment()
    {
        $this->qis->qecho("\nInitializing default testing environment...\n");

        $this->qis->qecho("This includes the following:\n");
        $this->qis->qecho(" - Create `tests` dir at the root of project\n");
        $this->qis->qecho(" - Create phpunit.xml file in `tests` dir\n");
        $this->qis->qecho(" - Create a default test file to start with\n\n");

        $input = readline("Do you want to continue? (Y/n): ");
        if (strtolower(trim($input)) != 'y' && trim($input) != '') {
            $this->qis->qecho("Exiting\n");
            return 0;
        }
        $this->qis->qecho("\n");

        $root = dirname($this->qis->getProjectQisRoot());

        // Make tests dir
        $testsdir = $root . '/tests';
        if (!is_dir($testsdir)) {
            $this->qis->qecho(sprintf("Creating directory `%s`\n", $testsdir));
            mkdir($testsdir);
        } else {
            $this->qis->qecho(sprintf("Directory `%s` already exists.\n", $testsdir));
        }

        // Write phpunit.xml file
        $phpunitConfig = $testsdir . '/phpunit.xml';
        if (!file_exists($phpunitConfig)) {
            $this->qis->qecho(sprintf("Writing file `%s`\n", $phpunitConfig));
            file_put_contents($phpunitConfig, $this->getDefaultPhpunitConfigXml());
        } else {
            $this->qis->qecho(sprintf("File `%s` already exists.\n", $phpunitConfig));
        }

        // Create first test
        $testsfileroot = $testsdir . '/src/' . ucfirst($this->qis->getConfig()->project_name);
        if (!is_dir($testsfileroot)) {
            $this->qis->qecho(sprintf("Creating directory `%s`\n", $testsfileroot));
            mkdir($testsfileroot, 0755, true);

            $testfile = $testsfileroot . '/DefaultTest.php';
            $this->qis->qecho(sprintf("Writing file `%s`\n", $testfile));
            file_put_contents($testfile, $this->getDefaultTestFile());
        }

        $this->qis->qecho("\nTest environment initialized. Now run `qis test` to run your first test.\n");
        $this->qis->qecho("Note that the phpunit.xml file defined assumes that the source\n");
        $this->qis->qecho("for this project is at `src`. If it needs to change, update the\n");
        $this->qis->qecho("value in coverage.include.directory in tests/phpunit.xml\n");

        return 0;
    }

    protected function getDefaultPhpunitConfigXml()
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.3/phpunit.xsd"
    backupGlobals="false"
    backupStaticAttributes="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    bootstrap="../vendor/autoload.php"
>
  <coverage processUncoveredFiles="true">
    <include>
      <directory>../src</directory>
    </include>
  </coverage>
  <testsuites>
    <testsuite name="Unit Tests">
      <directory>.</directory>
    </testsuite>
  </testsuites>
</phpunit>
EOF;
    }

    protected function getDefaultTestFile()
    {
        return <<<EOF
<?php

use PHPUnit\Framework\TestCase;

final class DefaultTest extends TestCase
{
    public function testDefault()
    {
        \$this->assertFalse(true);
    }
}
EOF;
    }
}
