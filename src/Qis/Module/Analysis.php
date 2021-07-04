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

        // Add specific options/params to args for this task
        $args->addRule('level:');
        $args->addRule('file:');
        $args->parse();

        $level = null;
        if ($args->level) {
            $level = $args->level;
        }

        if ($args->explain) {
            return $this->showExplain();
        }

        if ($args->results) {
            if ($args->file) {
                return $this->showResultsForFile($args->file);
            } else {
                return $this->showResults();
            }
        }

        if ($args->file) {
            if ($args->raw) {
                return $this->analyzeProject($level, true, $args->file);
            } else {
                $this->analyzeProject($level, false, $args->file);
                return $this->showResultsForFile($args->file);
            }
        }

        $is_saved = $this->analyzeProject($level, $args->raw);
        if ($is_saved) {
            $this->showResults($level);
        }

        $this->_qis->qecho("\nCompleted Analysis module task.\n");
        return ModuleInterface::RETURN_SUCCESS;
    }

    /**
     * Analyze project by running pdepend command
     *
     * @return bool
     */
    public function analyzeProject($level_override = null, $raw_output = false, $path_override = null)
    {
        $bin = $this->_settings['bin'];
        exec("which $bin", $output, $code);
        if ($code) {
            throw new \Exception("Cannot run executable '$bin'. Please check value defined in .qis/config.ini for analysis.bin\n");
        }

        $level = $this->_settings['level'] ?? 0;
        if ($level_override !== null) {
            $level = $level_override;
        }
        $jsonTarget = $this->_outputPath . 'results.json';

        $paths = "";
        foreach ($this->_paths as $path) {
            $paths .= "\"" . $path . "\" ";
        }

        if ($path_override) {
            $paths = $path_override;
        }

        if ($raw_output) {
            $cmd = sprintf('%s analyse --level=%s --no-progress %s', $bin, $level, $paths);
            $this->_qis->log($cmd);
            passthru($cmd);
            return false;
        }

        $cmd = sprintf('%s analyse --level=%s --error-format=prettyJson --memory-limit 200M --no-progress %s', $bin, $level, $paths);

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

        if (!$data) {
            throw new \Exception("No data in results file $filename.");
        }
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

    /**
     * Show the errors found
     *
     * @return string
     */
    public function showResults($level_override = null)
    {
        $terminal = $this->_qis->getTerminal();
        $results = $this->readResults();

        $level = $this->_settings['level'] ?? 0;
        if ($level_override !== null) {
            $level = $level_override;
        }

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
        printf("Level: %s\n", $level);
        $table->display();

        if ($this->getStatus()) {
            print($terminal->do_setaf(8) . $terminal->do_setab(2));
        } else {
            print($terminal->do_setaf(8) . $terminal->do_setab(1));
        }
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

        $col_1_width = 5;
        $col_2_width = $terminal->get_columns(true) - $col_1_width - 2;
        $hr_line = sprintf("%s %s\n", "-----", str_repeat("-", $col_2_width));
        foreach ($found_list as $filename => $file_results) {
            print($hr_line);
            printf("%s %s\n", "Line ", $filename);
            print($hr_line);
            foreach ($file_results->messages as $message_data) {
                printf(
                    "%s %s\n",
                    str_pad($message_data->line, $col_1_width),
                    self::wrap($message_data->message, $col_2_width)
                );
            }
            print("\n");
        }
    }

    public static function wrap($text, $width, $prefix_width = 7)
    {
        return wordwrap($text, $width, str_pad("\n", $prefix_width));
    }

    /**
     * Show results for file as table
     *
     * @param string $file
     * @return int
     */
    public function showResultsForFileAsTable($file)
    {
        $terminal = $this->_qis->getTerminal();
        $results = $this->readResults();

        $headers = ['', 'File:Line', 'Message'];
        $aligns = ['L', 'L', 'L'];

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
            . "--level : Perform analysis for level 0-8 (See --explain for explanation of levels)\n"
            . "--raw : Show the raw output of phpstan (instead of saving to results file)\n"
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

        $thresholds = $this->getThresholds();

        if (isset($thresholds['errors'])) {
            $threshold = $thresholds['errors'];
        } else {
            $threshold = 30;
        }

        if (isset($metrics['errors']) && $metrics['errors'] <= $threshold) {
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
            . "analysis.bin=vendor/bin/phpstan\n"
            . "analysis.level=0\n"
            . "analysis.paths=src,tests\n"
            . "analysis.thresholds[errors]=10\n"
            ;
    }
}
