<?php
/**
 * Coding Standard Module class file
 *
 * @package Qis
 */

/**
 * @see QisModuleInterface
 */
require_once 'QisModuleInterface.php';

/**
 * @see Qi_Db_PdoSqlite
 */
require_once 'Qi/Db/PdoSqlite.php';

/**
 * @see Utils
 */
require_once 'Utils.php';

/**
 * Coding Standard Module
 *
 * @uses QisModuleInterface
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qis_Module_Codingstandard implements QisModuleInterface
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
    protected $_standard = 'Zend';

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
     * Database object
     *
     * @var object
     */
    protected $_db = null;

    /**
     * Whether to include sniffcodes in result
     *
     * Required phpcs version 1.2 or higher.
     *
     * @var bool
     */
    protected $_includeSniffCodes = true;

    /**
     * Whether to use features in phpcs 1.3
     *
     * @var bool
     */
    protected $_phpcsVersionOneThree = true;

    /**
     * Options
     *
     * @var array
     */
    protected $_options = array(
        'phpslocbin' => 'phpsloc',
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

        $this->_paths = $this->_parsePath($this->_path);
    }

    /**
     * Set option
     *
     * @param string $name Option name
     * @param mixed $value Value
     * @return Qis_Module_Codingstandard
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
        $this->_checkPhpSloc();
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

        if ($status) {
            throw new Qis_Module_CodingStandardException(
                "PHPCodeSniffer (phpcs) not installed."
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

        if ((int) $major <= 1 && (int) $minor < 2) {
            $this->_includeSniffCodes = false;
            throw new Qis_Module_CodingStandardException(
                "phpcs version 1.2 or higher required."
            );
        }

        if ((int) $major <= 1 && (int) $minor < 3) {
            $this->_phpcsVersionOneThree = false;
        }

        return true;
    }

    /**
     * Check phpsloc is installed
     *
     * @return bool
     */
    protected function _checkPhpSloc()
    {
        $cmd = $this->_options['phpslocbin'] . " --help 2>&1";

        exec($cmd, $result, $status);

        if ($status == 127) {
            throw new Qis_Module_CodingStandardException(
                "phpsloc not installed."
            );
        }

        if (!empty($result)) {
            return true;
        }

        return false;
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
        if ($this->getErrorLevel() < 3) {
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
            $sniffStandard = 'Zend';
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

        if (!$this->_phpcsVersionOneThree) {
            // Fall back to functionality in phpcs 1.2.x
            $cmd = $this->_options['phpcsbin']
                . ' --standard=' . $sniffStandard
                . ' --extensions=php';

            // In direct mode the report should go to stdout
            if ($direct) {
                if ($paths == $this->_paths) {
                    // Show high-level summary
                    $cmd .= ' --report=summary';
                } else {
                    $cmd .= ' --report=full';
                }
            } else {
                $cmd .= ' --report=csv --report-file="'
                    . $this->_outputPath . 'results.csv"';
            }

            $cmd .= ' "' . implode('" "', $validPaths) . '"';

            if ($direct) {
                $cmd .= ' | tee "' . $this->_outputPath . 'output.log"';
            } else {
                $cmd .= ' > "' . $this->_outputPath . 'results.csv"';
            }
        } else {
            $cmd = $this->_options['phpcsbin']
                . ' --standard=' . $sniffStandard
                . ' -p'
                . ' --extensions=php';

            if ($paths == $this->_paths) {
                // Show high-level summary
                $cmd .= ' --report-summary=';
            } else {
                $cmd .= ' --report-full=';
            }

            $cmd .= ' --report-csv=' . $this->_outputPath . 'results.csv'
                . ' "' . implode('" "', $validPaths) . '" ';
        }

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
                $this->_db->safeQuery($sqlString);
            }
        } else {
            // Yes, just delete everything.
            $this->_db->safeQuery($sql);
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
            $this->_db->safeQuery($sql);

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

        $rows = $this->_db->getRows($sql);

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
        $this->_db->safeQuery($sql);
    }

    /**
     * Get Project Summary
     *
     * @return array
     */
    public function getProjectSummary()
    {
        $sql = "select * from project order by id desc limit 1;";

        return $this->_db->getRow($sql);
    }

    /**
     * Display summary
     *
     * @param bool $pretty Use pretty output
     * @return mixed
     */
    public function displaySummary($pretty = true)
    {
        include_once 'Qi/Console/Tabular.php';

        $project = $this->getProjectSummary();

        $results = array(
            'SLOC'        => $project['sloc'],
            'Comments'    => $project['comment_lines'],
            'Errors'      => $project['errors'],
            'Warnings'    => $project['warnings'],
            'Error Level' => $project['error_level'] . '%',
        );

        $table = new Qi_Console_Tabular(
            array(array_values($results)),
            array('headers' => array_keys($results))
        );

        $out = "Codingstandard results:\n" . $table->display(true);
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
     * @return void
     */
    public function displayList()
    {
        include_once 'Qi/Console/Tabular.php';

        $results = $this->_getFileList();

        // Determine common root from files
        $filelist = array();
        foreach ($results as $row) {
            $filelist[] = $row['file'];
        }
        $root = realpath('.') . DIRECTORY_SEPARATOR;
        //$commonRoot = Utils::findCommonRoot($filelist);
        //$root = $commonRoot;

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

        return 0;
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

        return $this->_db->getRows($sql);
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
            . "codingstandard.standard=Zend\n"
            . "codingstandard.path=.\n"
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

        $this->_db->safeQuery($sql);

        $sql = "create table snif_results (
            'id' integer primary key,
            'file' text,
            'line' integer,
            'column' integer,
            'severity' text,
            'message' text,
            'sniffcode' text
        );";

        $this->_db->safeQuery($sql);

        return true;
    }

    /**
     * Create project row
     *
     * @return void
     */
    protected function _createProjectRow()
    {
        $sql = "insert into project (datetime) values (" . time() . ");";

        $id = $this->_db->doSafeQuery($sql);
        return $id;
    }

    /**
     * Count source lines of code and comments
     *
     * @return array
     */
    public function countSloc()
    {
        $phpslocBin   = $this->_options['phpslocbin'];
        $filelistPath = $this->_outputPath . 'filelist';

        $this->_qis->log(
            "Counting lines of codes in paths '"
            . implode(',', $this->_paths) . "'"
        );

        $sloc     = 0;
        $comments = 1;

        $this->_clearFileList();

        foreach ($this->_paths as $path) {
            $this->_createFileList($path);
        }

        $cmd = $phpslocBin . " -c -f \"" . $filelistPath . "\"";

        $this->_qis->log($cmd);

        exec($cmd, $result, $status);
        if ($status === 0) {
            $totals   = explode(' ', end($result));
            $sloc     = $totals[0];
            $comments = $totals[1];
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
    }

    /**
     * Write to filelist for globbed path
     *
     * @param string $path Path to start globbing
     * @return void
     */
    protected function _createFileList($path)
    {
        $this->_qis->log("Creating file list for path `$path'");

        $files = Utils::rglob('*.php', 0, $path);
        $files = implode("\n", $files);

        $filelistPath = $this->_outputPath . 'filelist';

        file_put_contents($filelistPath, $files . "\n", FILE_APPEND);
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
        return $this->_db->safeQuery($sql);
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
        return $this->_db->safeQuery($sql);
    }
}

/**
 * Qis_Module_CodingStandardException
 *
 * @uses Exception
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qis_Module_CodingStandardException extends Exception
{
}
