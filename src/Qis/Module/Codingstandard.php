<?php
/**
 * Coding Standard Module class file
 *
 * @package Qis
 */

namespace Qis\Module;

use Qis\ModuleInterface;
use Qis\Qis;
use Qis\Utils;
use Qi_Console_ArgV;
use Qi_Db_PdoSqlite;
use Qi_Console_Tabular;
use SebastianBergmann\PHPLOC\Analyser;
use Exception;

/**
 * Coding Standard Module
 *
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Codingstandard implements ModuleInterface
{
    /**
     * Qis kernel object
     *
     * @var mixed
     */
    protected $_qis = null;

    /**
     * Output path
     *
     * @var string
     */
    protected $_outputPath = 'codingstandard';

    /**
     * Sniff standard
     *
     * @var string
     */
    protected $_standard = 'PSR2';

    /**
     * Path to start sniffing
     *
     * @var string
     */
    protected $_path = '.';

    /**
     * Paths to sniff
     *
     * @var array
     */
    protected $_paths = array();

    /**
     * List of files to sniff
     *
     * @var array
     */
    protected $files = array();

    /**
     * Ignore patterns to exclude during sniffing
     *
     * @var string
     */
    protected $_ignore = '';

    /**
     * Database object
     *
     * @var object
     */
    protected $_db = null;

    /**
     * Whether to include sniffcodes in result
     *
     * Required phpcs version 1.3 or higher.
     *
     * @var bool
     */
    protected $_includeSniffCodes = true;

    /**
     * Options
     *
     * @var array
     */
    protected $_options = array(
        'phpcsbin'   => 'phpcs',
    );

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

        if (isset($settings['standard'])
            && $settings['standard'] != ''
        ) {
            $this->_standard = $settings['standard'];
        }

        // Store the project path
        if (isset($settings['path'])
            && $settings['path'] != ''
        ) {
            $this->_path = $settings['path'];
        }

        if (isset($settings['ignore'])) {
            $this->_ignore = $settings['ignore'];
        }

        $this->_paths = $this->_parsePath($this->_path);
    }

    /**
     * Set option
     *
     * @param string $name Option name
     * @param mixed $value Value
     * @return Codingstandard
     */
    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;
        return $this;
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

        $this->_initDatabase();
    }

    /**
     * Check requirements
     *
     * @return void
     */
    protected function _checkRequirements()
    {
        $this->_checkVersion();
    }

    /**
     * Check version of phpcs
     *
     * @return bool
     */
    protected function _checkVersion()
    {
        $cmd = $this->_options['phpcsbin'] . ' --version 2>&1';
        exec($cmd, $result, $status);

        $this->_qis->log('Checking version of phpcs');

        if ($status != 0) {
            throw new CodingStandardException(
                "PHPCodeSniffer (phpcs) not installed. Please install with command `composer global require \"squizlabs/php_codesniffer\"`"
            );
        }

        if (!isset($result[0])) {
            $this->_qis->log(
                "Couldn't detect version of phpcs. No output from phpcs."
            );
            return false;
        }

        $this->_qis->log($result[0]);

        $foundMatch = preg_match(
            "/version (\d).(\d).(\d)/", $result[0], $matches
        );

        if (!$foundMatch) {
            $this->_qis->log("Version: " . $result[0]);
            $this->_qis->log("Couldn't detect version of phpcs");
            return false;
        }

        list($version, $major, $minor, $revision) = $matches;

        if ((int) $major <= 1 && (int) $minor < 3) {
            $this->_includeSniffCodes = false;
            throw new CodingStandardException(
                "phpcs version 1.3 or higher required."
            );
        }

        return true;
    }

    /**
     * Execute main functionality
     *
     * @param mixed $args Arguments
     * @return void
     */
    public function execute(Qi_Console_ArgV $args)
    {
        $this->_qis->qecho("\nRunning Codingstandard module task...\n");
        $this->_checkRequirements();

        if ($args->__arg2) {
            $paths = $this->_parsePath($args->__arg2);
            if (empty($paths)) {
                return $this->_qis->halt("Path `$args->__arg2' not found.");
            }
        } else {
            $paths = $this->_paths;
        }

        if ($args->list) {
            return $this->displayList();
        }

        $options = array(
            'direct' => (bool) $args->d,
        );

        $this->_runCodeSniff($paths, $options);

        $this->_qis->qecho("\nCompleted Codingstandard module task.\n");

        $this->displaySummary();

        return 0;
    }

    /**
     * Help message for this module
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return "Run coding standard validation report (phpcs)\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: cs [OPTIONS] [path]\n"
            . "By default this will run code sniffs for the "
            . "default project path(s).\n"
            . "You can specify a path to run sniffs for a certain file, "
            . "directory or\n"
            . "comma separated list of directories\n";

        $out .= "\nValid Options:\n"
            . $this->_qis->getTerminal()->do_setaf(3)
            . "  --list : Show list of files sniffed\n"
            . "  -d [--direct] : Output resulting report directly "
            . "(when not using default path)\n"
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
        if ($short) {
            $results = $this->getMetrics();
            if (!$results) {
                return "Codingstandard: No data.";
            }

            return 'Codingstandard error level: '
                . $this->getErrorLevel() . '%';
        }

        return $this->displaySummary(false);
    }

    /**
     * Get status for this module (pass/fail)
     *
     * @return bool
     */
    public function getStatus()
    {
        $errorLevel = $this->getErrorLevel();
        if ($errorLevel === null) {
            return false;
        }

        if ($errorLevel < 3) {
            return true;
        }

        return false;
    }

    /**
     * Run Code Sniff
     *
     * @param string $paths Paths to start sniffing
     * @param array $options Options
     * @return void
     */
    protected function _runCodeSniff($paths = array('.'), $options = array())
    {
        if ($this->_standard) {
            $sniffStandard = $this->_standard;
        } else {
            $sniffStandard = 'PSR2';
        }

        $direct = isset($options['direct']) && $options['direct'];

        $validPaths = array();
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                $this->_qis->halt("File '$path' doesn't exist.");
            }
            $validPaths[] = $path;
        }

        $this->_qis->qecho("Sniffing code with '$sniffStandard' standard...");
        if ($this->_qis->isVerbose()) {
            echo "\n";
        }

        $cmd = $this->_options['phpcsbin']
            . ' --standard=' . $sniffStandard
            . ' -p'
            . ' --extensions=php';

        if ($this->_ignore) {
            $cmd .= ' --ignore=' . escapeshellarg($this->_ignore);
        }

        if ($paths == $this->_paths) {
            // Show high-level summary
            $cmd .= ' --report=summary';
        } else {
            $cmd .= ' --report=full';
        }

        $cmd .= ' --report-csv=' . $this->_outputPath . 'results.csv'
            . ' "' . implode('" "', $validPaths) . '" ';

        $this->_qis->log($cmd);

        passthru($cmd);

        if (!$this->_qis->isVerbose()) {
            echo "done.\n";
        }

        if (!$direct) {
            if (!file_exists($this->_outputPath . 'results.csv')) {
                $this->_qis->warningMessage("No csv file to import.");
                return false;
            }
            try {
                $this->_saveTimeStamp();
                $this->countSloc();
                $this->_importCsv($this->_outputPath . 'results.csv', $paths);
                $this->_saveTotals();
            } catch (Exception $e) {
                $this->_qis->halt($e->getMessage());
            }
        }
    }

    /**
     * Import phpcs CSV results file
     *
     * @param string $csv CSV filename
     * @param string $paths Paths of file just sniffed
     * @return void
     */
    protected function _importCsv($csv, $paths = null)
    {
        if (null === $paths) {
            $paths = $this->_paths;
        }

        $sql = "DELETE FROM snif_results";

        // FIXME: This only correctly works if you are re-running
        // the sniff on a specific file, not a subdirectory
        if ($paths != $this->_paths) {
            // We ran the sniff for a specific file
            foreach ($paths as $path) {
                $sqlString = $sql . " WHERE file = '" . realpath($path) . "';";
                $this->_db->executeQuery($sqlString);
            }
        } else {
            // Yes, just delete everything.
            $this->_db->executeQuery($sql);
        }

        $row    = 0;
        $handle = fopen($csv, 'r');
        $cols   = fgetcsv($handle, 1000, ',');

        if (substr($cols[0], 0, 5) == 'ERROR') {
            throw new Exception("Error importing csv: " . $cols[0]);
        }

        if ($this->_qis->isVerbose()) {
            $this->_qis->log("Writing results to db");
        } else {
            echo "Writing results to db...";
        }

        $sqlPre = "INSERT INTO snif_results ('file', 'line', 'column', "
            . "'severity', 'message', 'sniffcode') VALUES ";

        $this->_db->beginTransaction();

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            // only add the row if there was a 1,
            // otherwise there was probably an error in the csv file
            if (!isset($data[1])) {
                continue;
            }

            if ($data[0] == $cols[0]) {
                // No data, we got the headers again.
                if ($this->_qis->isVerbose()) {
                    $this->_qis->log('No sniff results found.');
                }
                break;
            }

            if ($this->_includeSniffCodes) {
                $sniffCode = $this->_db->escape($data[5]);
            } else {
                $sniffCode = '';
            }

            $sqlRow = "('" . $this->_db->escape($data[0]) . "',"
                . $this->_db->escape($data[1]) . ","
                . $this->_db->escape($data[2]) . ","
                . "'" . $this->_db->escape($data[3]) . "',"
                . "'" . $this->_db->escape($data[4]) . "',"
                . "'" . $sniffCode . "')";

            $sql = $sqlPre . $sqlRow;
            $this->_db->executeQuery($sql);

            if ($this->_qis->isVerbose()) {
                echo '.';
            }

            $row++;
        }

        $this->_db->commit();

        if ($this->_qis->isVerbose()) {
            $this->_qis->log('Finished writing results to db.');
        } else {
            echo "done\n";
        }

        return true;
    }

    /**
     * Get the error and warning counts and save that to the project record
     *
     * @return void
     */
    protected function _saveTotals()
    {
        $sql = "select severity, count(id) as count "
            . "from snif_results "
            . "group by severity "
            . "order by severity;";

        $rows = $this->_db->fetchRows($sql);

        $errorTotal   = 0;
        $warningTotal = 0;

        foreach ($rows as $row) {
            switch($row['severity']) {
            case 'error':
                $errorTotal = $row['count'];
                break;
            case 'warning':
                $warningTotal = $row['count'];
                break;
            }
        }

        $project    = $this->getProjectSummary();
        $errorLevel = $this->calculateErrorLevel(
            $errorTotal, $warningTotal,
            $project['sloc'], $project['comment_lines']
        );

        $sql = "update project set errors=$errorTotal, "
            . "warnings=$warningTotal, error_level=$errorLevel;";
        $this->_db->executeQuery($sql);

        return $errorLevel;
    }

    /**
     * Get Project Summary
     *
     * @return array
     */
    public function getProjectSummary()
    {
        $sql = "select * from project order by id desc limit 1;";

        return $this->_db->fetchRow($sql);
    }

    /**
     * Get metrics for current results
     *
     * @param bool $onlyPrimary Return only the primary metric
     * @return array|float
     */
    public function getMetrics($onlyPrimary = false)
    {
        $project = $this->getProjectSummary();
        if ($project['error_level'] === null) {
            return [];
        }

        $results = [
            'SLOC'        => $project['sloc'],
            'Comments'    => $project['comment_lines'],
            'Errors'      => $project['errors'],
            'Warnings'    => $project['warnings'],
            'Error Level' => $project['error_level'] . '%',
        ];

        if ($onlyPrimary) {
            return 100 - $project['error_level'];
        }

        return $results;
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

        $table = new Qi_Console_Tabular(
            array(array_values($results)),
            array('headers' => array_keys($results))
        );


        $out = "Codingstandard results:\n";
        if (!$results) {
            $out .= "No results.";
        } else {
            $out .= $table->display(true);
        }

        if ($pretty) {
            $this->_qis->prettyMessage(trim($out), 8, 4);
        } else {
            return $out;
        }
    }

    /**
     * Get error level
     *
     * @return float
     */
    public function getErrorLevel()
    {
        $project = $this->getProjectSummary();
        return $project['error_level'];
    }

    /**
     * Display a list of files with error levels from database
     *
     * @return int
     */
    public function displayList()
    {
        $results = $this->_getFileList();

        // Determine common root from files
        $filelist = array();
        foreach ($results as $row) {
            $filelist[] = $row['file'];
        }
        $root = realpath('.') . DIRECTORY_SEPARATOR;

        // Group by severity to get error and warning counts
        $summary = array();
        foreach ($results as $row) {
            $file = str_replace($root, '', $row['file']);

            $summary[$file][$row['severity']] = $row['count'];
        }

        // Reformulate rows to include an error count
        // and warning count for each file
        $rows = array();
        foreach ($summary as $file => $counts) {
            $row = array(
                'file'    => $file,
                'error'   => 0,
                'warning' => 0,
            );
            if (isset($counts['error'])) {
                $row['error'] = $counts['error'];
            }
            if (isset($counts['warning'])) {
                $row['warning'] = $counts['warning'];
            }
            $rows[] = $row;
        }

        // Generate a terminal table display
        $table = new Qi_Console_Tabular(
            $rows,
            array(
                'headers'   => array('file', 'errors', 'warnings'),
                'cellalign' => array('L', 'R', 'R'),
            )
        );

        echo $table->display(true);

        return ModuleInterface::RETURN_BENIGN;
    }

    /**
     * Get file list from database
     *
     * @return array
     */
    protected function _getFileList()
    {
        $sql = "select file, severity, count(id) as `count` "
            . "from snif_results "
            . "group by file, severity "
            . "order by file, severity";

        return $this->_db->fetchRows($sql);
    }

    /**
     * Calculate error level percent
     *
     * @param int $errors The error count
     * @param int $warnings The warning count
     * @param int $sloc The sloc count (source lines of code)
     * @param int $comments The comment lines count
     * @return float
     */
    public function calculateErrorLevel($errors, $warnings, $sloc,
        $comments = 0)
    {
        $errorPoints = $errors + ($warnings / 2);

        if ($sloc > 0) {
            $errorLevel = $errorPoints / ($sloc + $comments) * 100;
        } else {
            $errorLevel = 0;
        }

        return round($errorLevel, 2);
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

        $this->_qis->log("Saving timestamp of $contents");

        return file_put_contents($file, $contents);
    }

    /**
     * Get default ini settings for this module
     *
     * @return string
     */
    public static function getDefaultIni()
    {
        return "; Module to run codesniffs to check coding standards.\n"
            . "codingstandard.command=cs\n"
            . "codingstandard.class=" . get_called_class() . "\n"
            . "codingstandard.standard=PSR2\n"
            . "codingstandard.path=.\n"
            . "codingstandard.ignore=vendor\n"
            ;
    }

    /**
     * Parse path and return an array of paths
     *
     * Explodes a comma separated list of paths into full paths
     *
     * @param string $path Path
     * @return array
     */
    protected function _parsePath($path)
    {
        if (strpos($path, ',') !== false) {
            $paths = explode(',', $path);
        } else {
            $paths = array($path);
        }

        $fullpaths = array();
        foreach ($paths as $path) {
            $fullpath = realpath(trim($path));
            if ($fullpath != '') {
                $fullpaths[] = $fullpath;
            }
        }

        return $fullpaths;
    }

    /**
     * Initialize database
     *
     * @return void
     */
    protected function _initDatabase()
    {
        $cfg = array(
            'dbfile'   => $this->_outputPath . 'cs.db3',
            'log'      => true,
            'log_file' => $this->_outputPath . 'db.log',
        );

        $createSchema = false;
        if (!file_exists($cfg['dbfile'])) {
            // If this is a first time, set a flag to create the tables
            $createSchema = true;
        }

        $this->_db = new Qi_Db_PdoSqlite($cfg);

        if ($createSchema) {
            // First time db setup
            $this->_createSchema();
            $this->_createProjectRow();
        }
    }

    /**
     * Create db schema
     *
     * @return bool
     */
    protected function _createSchema()
    {
        $this->_qis->log("Creating cs database schema");

        $sql = "create table project (
            'id' integer primary key,
            'datetime' integer,
            'sloc' integer,
            'comment_lines' integer,
            'errors' integer,
            'warnings' integer,
            'error_level' real
        );";

        $this->_db->executeQuery($sql);

        $sql = "create table snif_results (
            'id' integer primary key,
            'file' text,
            'line' integer,
            'column' integer,
            'severity' text,
            'message' text,
            'sniffcode' text
        );";

        $this->_db->executeQuery($sql);

        return true;
    }

    /**
     * Create project row
     *
     * @return void
     */
    protected function _createProjectRow()
    {
        $sql = "INSERT INTO project (datetime) values (" . time() . ");";

        $id = $this->_db->executeQuery($sql);
        return $id;
    }

    /**
     * Count source lines of code and comments
     *
     * @return array
     */
    public function countSloc()
    {
        $filelistPath = $this->_outputPath . 'filelist';

        $this->_qis->log(
            "Counting lines of codes in paths '"
            . implode(',', $this->_paths) . "'"
        );

        $sloc     = 0;
        $comments = 1;

        $this->_clearFileList();

        foreach ($this->_paths as $path) {
            $files = $this->_createFileList($path);
        }

        $analyser = new Analyser();
        $results = $analyser->countFiles($files, false);

        if (isset($results['loc'])) {
            // Lines of Code
            $sloc = $results['loc'];
        }

        if (isset($results['cloc'])) {
            // Lines of Code
            $comments = $results['cloc'];
        }

        $this->_updateSloc($sloc);
        $this->_updateCommentLines($comments);

        return array($sloc, $comments);
    }

    /**
     * Clear filelist
     *
     * @return void
     */
    protected function _clearFileList()
    {
        $filelistPath = $this->_outputPath . 'filelist';
        if (file_exists($filelistPath)) {
            unlink($filelistPath);
        }

        $this->files = array();
    }

    /**
     * Write to filelist for globbed path
     *
     * @param string $path Path to start globbing
     * @return array
     */
    protected function _createFileList($path)
    {
        // Ensure path ends in single slash
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $this->_qis->log("Creating file list for path `$path'");

        $files = Utils::rglob('*.php', 0, $path);

        $files = $this->_filterIgnoredFiles($files);
        $filelist = implode("\n", $files);

        $filelistPath = $this->_outputPath . 'filelist';

        file_put_contents($filelistPath, $filelist . "\n", FILE_APPEND);

        $this->files = array_merge($this->files, $files);

        return $this->files;
    }

    /**
     * Filter files that should be ignored
     *
     * @param array $files Array of file paths
     * @return array Filtered array of file paths
     */
    protected function _filterIgnoredFiles($files)
    {
        if ($this->_ignore == '') {
            return $files;
        }

        $ignorePattern = str_replace(',', '|', $this->_ignore);

        // Filter out ignored paths
        $filtered = array();
        foreach ($files as $file) {
            if (preg_match('#' . $ignorePattern . '#', $file)) {
                continue;
            }
            $filtered[] = $file;
        }

        $message = sprintf(
            "Filtered %s of %s files in list.",
            count($files) - count($filtered),
            count($files)
        );

        $this->_qis->log($message);

        return $filtered;
    }

    /**
     * Update sloc in db
     *
     * @param int $total Total source lines
     * @return void
     */
    protected function _updateSloc($total)
    {
        $sql = "update project set sloc=$total";
        return $this->_db->executeQuery($sql);
    }

    /**
     * Update comment lines total
     *
     * @param int $total Total comment lines
     * @return mixed
     */
    protected function _updateCommentLines($total)
    {
        $sql = "update project set comment_lines=$total";
        return $this->_db->executeQuery($sql);
    }
}

/**
 * Qis Module CodingStandardException
 *
 * @uses Exception
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class CodingStandardException extends Exception
{
}
