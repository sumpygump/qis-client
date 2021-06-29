<?php

namespace Qis\Module;

use Qis\ModuleInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Tabular;
use Exception;

class Analysis implements ModuleInterface
{
    /**
     * Output path
     *
     * @var string
     */
    protected $_outputPath = 'phpstan';

    /**
     * Qis kernel object
     *
     * @var Qis
     */
    protected $_qis = null;

    /**
     * Settings
     *
     * @var array
     */
    protected $_settings = [];

    /**
     * Path (root of project to analyze)
     *
     * This can be overridden in the config
     * For example if you want to define it
     * in the phpunit configuration xml
     *
     * @var string
     */
    protected $_paths = ['src'];

    /**
     * Constructor
     *
     * @param Qis $qis Qis object
     * @param array $settings Configuration settings
     * @return void
     */
    public function __construct(Qis $qis, $settings)
    {
        $this->_qis      = $qis;
        $this->_settings = $settings;

        // Store the target analysis paths
        if (isset($settings['paths'])) {
            $paths = $settings['paths'];

            if (strpos($paths, ",") !== false) {
                $paths = explode(",", $paths);
            } else {
                $paths = [$paths];
            }

            $this->_paths = $paths;
        }
    }

    /**
     * Initialize this module
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
     * Execute main logic for this module
     *
     * @param Qi_Console_ArgV $args Arguments
     * @return int
     */
    public function execute(Qi_Console_ArgV $args)
    {
        $this->_qis->qecho("\nRunning Analysis module task...\n");

        $level = null;
        if ($args->level) {
            $level = $args->__arg2;
        }

        if ($args->explain) {
            return $this->showExplain();
        }

        if ($args->results) {
            return $this->showResults();
        }

        if ($args->file) {
            $file = $args->__arg2;
            return $this->showResultsForFile($file);
        }

        $is_saved = $this->analyzeProject($level, $args->raw);
        if ($is_saved) {
            $this->showResults();
        }

        $this->_qis->qecho("\nCompleted Analysis module task.\n");
        return ModuleInterface::RETURN_SUCCESS;
    }

    /**
     * Analyze project by running pdepend command
     *
     * @return bool
     */
    public function analyzeProject($level_override = null, $raw_output = false)
    {
        $bin = $this->_settings['bin'];
        $level = $this->_settings['level'] ?? 0;
        if ($level_override !== null) {
            $level = $level_override;
        }
        $jsonTarget = $this->_outputPath . 'results.json';

        $paths = "";
        foreach ($this->_paths as $path) {
            $paths .= "\"" . $path . "\" ";
        }

        if ($raw_output) {
            $cmd = sprintf('%s analyse --level=%s --no-progress %s', $bin, $level, $paths);
            $this->_qis->log($cmd);
            passthru($cmd);
            return false;
        }

        $cmd = sprintf('%s analyse --level=%s --error-format=prettyJson --no-progress %s', $bin, $level, $paths);

        $this->_qis->log($cmd);

        ob_start();
        passthru($cmd);
        $results = ob_get_clean();
        file_put_contents($jsonTarget, $results);

        $this->saveTimeStamp();
        return true;
    }

    public function readResults()
    {
        $filename = $this->_outputPath . 'results.json';
        $this->_qis->log($filename);

        // Load the file
        if (!file_exists($filename)) {
            throw new \Exception("File '$filename' not found.");
        }

        $data = file_get_contents($filename);
        return json_decode($data);
    }

    public function getThresholds()
    {
        if (!isset($this->_settings['thresholds'])) {
            return [];
        }

        return $this->_settings['thresholds'];
    }

    public function getHeader($header, $show_metrics)
    {
        $header_metrics = array_map('strtoupper', $show_metrics);
        return array_merge($header, $header_metrics);
    }

    public function addToRow(&$row, $group, $show_metrics, $thresholds, $prefix, $escapes)
    {
        foreach ($show_metrics as $metric_id) {
            $metric = $group[$metric_id];
            if (isset($thresholds[$prefix . $metric_id])
                && $metric > $thresholds[$prefix . $metric_id]
            ) {
                $row[] = $escapes['white-bgred'] . $group[$metric_id] . $escapes['op'];
            } else {
                $row[] = $group[$metric_id];
            }
        }
    }

    /**
     * Show the errors found
     *
     * @return string
     */
    public function showResults()
    {
        $terminal = $this->_qis->getTerminal();
        $results = $this->readResults();

        $headers = ['', 'File:Line', 'Message'];
        $aligns = ['L', 'L', 'L'];

        $root = realpath('.') . DIRECTORY_SEPARATOR;
        $rows = [];
        $i = 1;
        foreach ($results->files as $filename => $file_results) {
            $_filename = str_replace($root, '', $filename);
            foreach ($file_results->messages as $message_data) {
                $rows[] = [$i++, $_filename . ':' . $message_data->line, $message_data->message];
            }
        }

        $table = new Qi_Console_Tabular(
            $rows,
            ['headers' => $headers, 'cellalign' => $aligns, 'border' => true]
        );

        printf("Last run: %s\n", $this->getLastRunTimeStamp());
        printf("Level: %s\n", $this->_settings['level'] ?? 0);
        $table->display();
        print($terminal->do_setaf(8) . $terminal->do_setab(1));
        printf("Total errors: %s", $results->totals->file_errors);
        print($terminal->do_op());
        print "\n\nUse `qis analysis --file <filename>` to show results per file.\n";
    }

    /**
     * Show results for file
     *
     * @param string $file
     * @return int
     */
    public function showResultsForFile($file)
    {
        $terminal = $this->_qis->getTerminal();
        $results = $this->readResults();

        $headers = ['', 'File:Line', 'Message'];
        $aligns = ['L', 'L', 'L'];

        $thresholds = $this->getThresholds();

        $root = realpath('.') . DIRECTORY_SEPARATOR;

        $found_list = [];
        foreach ($results->files as $filename => $file_results) {
            $_filename = str_replace($root, '', $filename);
            if (strpos($_filename, trim($file)) !== false) {
                $found_list[$_filename] = $file_results;
            }
        }

        if (count($found_list) == 0) {
            printf("No results for file '%s'\n", $file);
            return 1;
        }

        $rows = [];
        $total_errors = 0;
        $i = 1;
        foreach ($found_list as $filename => $file_results) {
            foreach ($file_results->messages as $message_data) {
                $rows[] = [$i++, $_filename . ':' . $message_data->line, $message_data->message];
                $total_errors += 1;
            }
        }

        $table = new Qi_Console_Tabular(
            $rows,
            ['headers' => $headers, 'cellalign' => $aligns]
        );

        printf("Level: %s   Last run: %s\n", $this->_settings['level'] ?? 0, $this->getLastRunTimeStamp());
        $table->display();
        print($terminal->do_setaf(8) . $terminal->do_setab(1));
        if (count($found_list) == 1) {
            $found = reset($found_list);
            printf("File errors: %s", $found->errors);
        } else {
            printf("Errors: %s", $total_errors);
        }
        print($terminal->do_op());
        print("\n\n");
    }

    /**
     * Print out an explanation of the metrics
     *
     * @return null
     */
    public function showExplain()
    {
        print "An explanation of the rule levels for phpstan analysis.\n";
        print "-------------------------------------------------------\n";
        print "0. basic checks, unknown classes, unknown functions, unknown\n"
            . "   methods called on \$this, wrong number of arguments passed to\n"
            . "   those methods and functions, always undefined variables\n";
        print "1. possibly undefined variables, unknown magic methods and\n"
            . "   properties on classes with __call and __get\n";
        print "2. unknown methods checked on all expressions (not just \$this),\n"
            . "   validating PHPDocs\n";
        print "3. return types, types assigned to properties\n";
        print "4. basic dead code checking - always false instanceof and other\n"
            . "   type checks, dead else branches, unreachable code after return; etc.\n";
        print "5. checking types of arguments passed to methods and functions\n";
        print "6. report missing typehints\n";
        print "7. report partially wrong union types - if you call a method that\n"
            . "   only exists on some types in a union type, level 7 starts to\n"
            . "   report that; other possibly incorrect situations\n";
        print "8. report calling methods and accessing properties on nullable types\n";

        return null;
    }

    /**
     * Get help message for this module
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return "Perform static analysis for project (runs phpstan)\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: analysis [OPTIONS] [path]\n"
            . "This will run static analysis tool on the project files.\n\n"
            . "Valid Options:\n"
            . $this->_qis->getTerminal()->do_setaf(3)
            . "--results : Show results from last run\n"
            . "--file <name> : Show results for a specific file\n"
            . "--explain : Show explanation of levels\n"
            . $this->_qis->getTerminal()->do_op();

        return $out;
    }

    /**
     * Get status of this module
     *
     * @return bool
     */
    public function getStatus()
    {
        $metrics = $this->getMetrics();

        if (!$metrics) {
            return false;
        }

        if ($metrics['error_score'] >= 25) {
            return true;
        }

        return false;
    }

    /**
     * Save timestamp
     *
     * @return bool
     */
    protected function saveTimeStamp()
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
     * Get summary for this module
     *
     * @param bool $short Return short message
     * @return string
     */
    public function getSummary($short = false)
    {
        try {
            $results = $this->readResults();
        } catch (\Exception $e) {
            if ($short) {
                return "Analysis: No data.";
            } else {
                return "Analysis results";
            }
        }
        $level = $this->_settings['level'] ?? 0;

        if ($short) {
            return sprintf("Analysis errors (L%s): %s", $level, $results->totals->file_errors);
        }

        return $this->displaySummary();

    }

    public function displaySummary()
    {
        $results = $this->getMetrics();
        if (false === $results) {
            return 'No data yet.';
        }

        $table = new Qi_Console_Tabular(
            array(array_values($results)),
            array('headers' => array_keys($results))
        );
        $out = "Analysis results:\n" . $table->display(true);

        return $out;
    }

    /**
     * Get metrics for current status
     *
     * @param bool $onlyPrimary Return only the primary metric
     * @return array|float
     */
    public function getMetrics($onlyPrimary = false)
    {
        try {
            $results = $this->readResults();
        } catch (\Exception $e) {
            return false;
        }
        if (!isset($results->totals->file_errors)) {
            return false;
        }

        $errors = $results->totals->file_errors;
        if ($errors == 0) {
            $metric = 100;
        } else {
            $metric = round((1 / $errors) * 100, 2);
        }

        if ($onlyPrimary) {
            return $metric;
        }

        return [
            'level' => $this->_settings['level'] ?? 0,
            'errors' => $errors,
            'error_score' => $metric,
        ];
    }

    /**
     * Get default ini settings for this module
     *
     * @return string
     */
    public static function getDefaultIni()
    {
        return "; Perform static analysis on project classes and methods\n"
            . "analysis.command=analysis\n"
            . "analysis.class=" . get_called_class() . "\n"
            . "analysis.bin=phpstan\n"
            . "analysis.paths=src,tests\n"
            . "analysis.thresholds[errors]=10\n"
            ;
    }
}
