<?php

/**
 * Coverage Module class
 *
 * @package Qis
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qis\Module;

use Qis\ModuleInterface;
use Qis\Qis;
use Qis\CloverCoverageReport;
use Qi_Console_ArgV;
use Exception;

/**
 * Coverage Module class
 *
 * @uses \Qis\ModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class Coverage implements ModuleInterface
{
    /**
     * Storage of Qis object
     *
     * @var object
     */
    protected $qis = null;

    /**
     * Path
     *
     * @var string
     */
    protected $root = '.';

    /**
     * Output path
     *
     * @var string
     */
    protected $outputPath = 'coverage';

    /**
     * A list of ignore paths
     *
     * @var array
     */
    protected $ignorePaths = [];

    /**
     * Args from last execution
     *
     * @var array<string, string>
     */
    protected $args = [];

    /**
     * Get default ini settings for this module
     *
     * @return string
     */
    public static function getDefaultIni()
    {
        return "; Module for code coverage of unit tests.\n"
            . "coverage.command=coverage\n"
            . "coverage.class=" . get_called_class() . "\n"
            . "coverage.root=.\n"
            . "coverage.ignorePaths=vendor,SymfonyComponents\n"
            ;
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
        $this->qis = $qis;

        if (isset($settings['root']) && trim($settings['root'])) {
            $this->root = $settings['root'];
        }

        if (isset($settings['ignorePaths']) && trim($settings['ignorePaths'])) {
            $this->ignorePaths = explode(',', trim($settings['ignorePaths']));
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

        $this->root = realpath($this->root) . DIRECTORY_SEPARATOR;
    }

    /**
     * Execute module
     *
     * @param Qi_Console_ArgV $args Arguments
     * @return int
     */
    public function execute(Qi_Console_ArgV $args)
    {
        if ($args->__arg2) {
            $targetFile = $args->__arg2;
        } else {
            $targetFile = null;
        }
        $this->args = $args->toArray();

        $this->saveTimeStamp();
        try {
            if ($args->serve) {
                $this->serveCoverage();
                return 0;
            } else {
                ob_start();
                $this->qis->qecho("\nRunning coverage module task...\n");

                $this->checkCoverage($targetFile);
            }
        } catch (Exception $e) {
            // If there was an exception, eat the output from ob
            if (ob_get_level()) {
                ob_end_clean();
            }
            throw $e;
        }

        ob_end_flush();

        $this->qis->qecho("\nCompleted coverage module task.\n");

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
     * Get help message for this module
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return "Show code coverage for unit tests.\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: coverage [OPTIONS] [filename]\n"
            . "By default this will show a coverage report for project files.\n"
            . "If a filename is specified, "
            . "a source file coverage report is displayed\n"
            . "for the given filename.\n";

        $out .= "\nValid Options:\n"
            . $this->qis->getTerminal()->do_setaf(3)
            . "  --list : Show list of files in coverage\n"
            . "  --serve : Serve the html coverage report on port 8005\n"
            . $this->qis->getTerminal()->do_op();

        return $out;
    }

    /**
     * Get summary for this module
     *
     * @param bool $short Get short summary
     * @return string
     */
    public function getSummary($short = false)
    {
        $out = '';

        if ($short) {
            $out .= "Coverage: ";
        } else {
            $out .= "Coverage results:\n";
        }

        return $out . $this->getTotalCoverage();
    }

    /**
     * Get metrics for current results
     *
     * @param bool $onlyPrimary Only return primary metric
     * @return array|float
     */
    public function getMetrics($onlyPrimary = false)
    {
        $totalCoverageFloat = 0.0;

        $totalCoverageString = $this->getTotalCoverage();

        // The string says "Total Coverage: <float>%"
        $results = sscanf($totalCoverageString, "%s %s %f");
        if (isset($results[2])) {
            $totalCoverageFloat = $results[2];
        }

        if ($onlyPrimary) {
            return $totalCoverageFloat;
        }

        return [
            'coverage' => $totalCoverageFloat,
        ];
    }

    /**
     * Get status for this module (pass/fail)
     *
     * @return bool
     */
    public function getStatus()
    {
        $metrics = $this->getMetrics();

        $totalCoverageFloat = $metrics['coverage'];

        return $totalCoverageFloat > 80.0;
    }

    /**
     * Save timestamp
     *
     * @return bool
     */
    protected function saveTimeStamp()
    {
        $file     = $this->outputPath . 'lastrun';
        $contents = date('Y-m-d H:i:s');

        return file_put_contents($file, $contents);
    }

    /**
     * Check coverage
     *
     * @param string $targetFile Target file
     * @return void
     */
    protected function checkCoverage($targetFile = null)
    {
        $file = $this->qis->getProjectQisRoot() . DIRECTORY_SEPARATOR
            . 'test-results' . DIRECTORY_SEPARATOR . 'coverage.xml';

        if (!file_exists($file)) {
            throw new CoverageException(
                "Cannot find file '$file'. "
                . "Ensure test module is executed first."
            );
        }

        $this->qis->log('Parsing clover coverage report...');
        $report = new CloverCoverageReport(
            $file,
            $targetFile,
            $this->root,
            $this->ignorePaths
        );

        $totalCoverage = $report->getTotalCoverage();

        $this->saveTotalCoverage($totalCoverage);
    }

    protected function serveCoverage()
    {
        $path = $this->qis->getProjectQisRoot() . DIRECTORY_SEPARATOR
            . 'test-results' . DIRECTORY_SEPARATOR . 'coverage';

        if (!is_dir($path)) {
            throw new CoverageException(
                "Cannot serve coverage report. "
                . "Ensure test module is executed first and"
                . "`test.coverage-html` is set to true in .qis/config.ini"
            );
        }

        echo "Attempting to serve coverage report at http://localhost:8005 ...\n";

        $cmd = "php -S 127.0.0.1:8005 -t \"$path\" &2>1";
        flush();
        passthru($cmd);
    }

    /**
     * Save total coverage to disk to be used by summary
     *
     * @param float $totalCoverage Total coverage percentage
     * @return mixed
     */
    protected function saveTotalCoverage($totalCoverage)
    {
        $file = $this->outputPath . 'totalcoverage.txt';

        $contents = "Total Coverage: " . $totalCoverage . "%";
        return file_put_contents($file, $contents);
    }

    /**
     * Get total coverage from disk from last run.
     *
     * @return string
     */
    public function getTotalCoverage()
    {
        $file = $this->outputPath . 'totalcoverage.txt';
        $out  = '';

        if (!file_exists($file)) {
            $out .= 'No data.';
        } else {
            $out .= file_get_contents($file);
        }

        return $out;
    }
}

/**
 * Qis Module CoverageException
 *
 * @uses \Exception
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class CoverageException extends Exception
{
}
