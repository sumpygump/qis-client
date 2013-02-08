<?php
/**
 * Coverage Module class
 *
 * @package Qis
 */

/**
 * @see QisModuleInterface
 */
require_once 'QisModuleInterface.php';

/**
 * Coverage Module class
 * 
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qis_Module_Coverage implements QisModuleInterface
{
    /**
     * Storage of Qis object
     * 
     * @var object
     */
    protected $_qis = null;

    /**
     * Path
     * 
     * @var string
     */
    protected $_root = '.';

    /**
     * Output path
     * 
     * @var string
     */
    protected $_outputPath = 'coverage';

    /**
     * A list of ignore paths
     * 
     * @var array
     */
    protected $_ignorePaths = array();

    /**
     * Get default ini settings for this module
     * 
     * @return string
     */
    public static function getDefaultIni()
    {
        return "; Module for code coverage of unit tests.\n"
            . "coverage.command=coverage\n"
            . "coverage.class=" . get_called_class() ."\n"
            . "coverage.root=.\n"
            . "coverage.ignorePaths=\n"
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
        $this->_qis = $qis;

        if (isset($settings['root']) && trim($settings['root'])) {
            $this->_root = $settings['root'];
        }

        if (isset($settings['ignorePaths']) && trim($settings['ignorePaths'])) {
            $this->_ignorePaths = explode(',', trim($settings['ignorePaths']));
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

        $this->_root = realpath($this->_root) . DIRECTORY_SEPARATOR;
    }

    /**
     * Execute module
     * 
     * @param Qi_Console_ArgV $args Arguments
     * @return int
     */
    public function execute(Qi_Console_ArgV $args)
    {
        ob_start();
        $this->_qis->qecho("\nRunning coverage module task...\n");

        if ($args->__arg2) {
            $targetFile = $args->__arg2;
        } else {
            $targetFile = null;
        }

        $this->_saveTimeStamp();
        try {
            $this->_checkCoverage($targetFile);
        } catch (Exception $e) {
            // If there was an exception, eat the output from ob
            ob_clean();
            throw $e;
        }

        $result = ob_get_contents();
        ob_end_clean();
        echo $result;

        $this->_qis->qecho("\nCompleted coverage module task.\n");

        return 0;
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
            . $this->_qis->getTerminal()->do_setaf(3)
            . "  --list : Show list of files in coverage\n"
            . $this->_qis->getTerminal()->do_op();

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
     * Get status for this module (pass/fail)
     * 
     * @return bool
     */
    public function getStatus()
    {
        $totalCoverageFloat = 0.0;

        $totalCoverageString = $this->getTotalCoverage();

        // The string says "Total Coverage: <float>%"
        $results = sscanf($totalCoverageString, "%s %s %f");
        if (isset($results[2])) {
            $totalCoverageFloat = $results[2];
        }

        return $totalCoverageFloat > 80.0;
    }

    /**
     * Save timestamp
     * 
     * @return bool
     */
    protected function _saveTimeStamp()
    {
        $file     = $this->_outputPath . 'lastrun';
        $contents = date('Y-m-d H:i:s');

        return file_put_contents($file, $contents);
    }

    /**
     * Check coverage
     * 
     * @param string $targetFile Target file
     * @return void
     */
    protected function _checkCoverage($targetFile = null)
    {
        $file = $this->_qis->getProjectQisRoot() . DIRECTORY_SEPARATOR
            . 'test-results' . DIRECTORY_SEPARATOR . 'coverage.xml';

        if (!file_exists($file)) {
            throw new Qis_Module_CoverageException(
                "Cannot find file '$file'. "
                . "Ensure test module is executed first."
            );
        }

        include_once 'CloverCoverageReport.php';

        $this->_qis->log('Parsing clover coverage report...');
        $report = new CloverCoverageReport(
            $file, $targetFile, $this->_root, $this->_ignorePaths
        );

        $totalCoverage = $report->getTotalCoverage();

        $this->_saveTotalCoverage($totalCoverage);
    }

    /**
     * Save total coverage to disk to be used by summary
     * 
     * @param float $totalCoverage Total coverage percentage
     * @return mixed
     */
    protected function _saveTotalCoverage($totalCoverage)
    {
        $file = $this->_outputPath . 'totalcoverage.txt';

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
        $file = $this->_outputPath . 'totalcoverage.txt';
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
 * Qis_Module_CoverageException
 *
 * @uses Exception
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qis_Module_CoverageException extends Exception
{
}
