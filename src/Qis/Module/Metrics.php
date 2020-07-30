<?php

namespace Qis\Module;

use Qis\ModuleInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Tabular;
use Qis\PdependSummaryReport;
use Exception;

class Metrics implements ModuleInterface
{
    /**
     * Output path
     *
     * @var string
     */
    protected $_outputPath = 'pdepend';

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
    protected $_settings = array();

    /**
     * Path (root of project to analyze)
     *
     * This can be overridden in the config
     * For example if you want to define it
     * in the phpunit configuration xml
     *
     * @var string
     */
    protected $_path = 'src';

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

        // Store the project root path
        if (isset($settings['path'])) {
            $this->_path = $settings['path'];
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
        $this->_qis->qecho("\nRunning Metrics module task...\n");

        if ($args->explain) {
            return $this->showExplain();
        }

        if ($args->results) {
            return $this->showMetrics();
        }

        if ($args->class) {
            $class = $args->__arg2;
            return $this->showMetricsForClass($class);
        }

        $this->analyzeProject();
        return $this->showMetrics();

        $this->_qis->qecho("\nCompleted Metrics module task.\n");
        return ModuleInterface::RETURN_SUCCESS;
    }

    /**
     * Analyze project by running pdepend command
     *
     * @return void
     */
    public function analyzeProject()
    {
        $bin = $this->_settings['bin'];
        $xmlTarget = $this->_outputPath . 'summary.xml';
        $cmd = sprintf('%s --summary-xml="%s" "%s"', $bin, $xmlTarget, $this->_path);

        $this->_qis->log($cmd);

        passthru($cmd);
    }

    public function readMetrics()
    {
        $this->_qis->log($this->_outputPath . 'summary.xml');
        $summaryParser = new PdependSummaryReport($this->_outputPath . 'summary.xml');
        return $summaryParser->parse();
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
     * Show the statistics
     *
     * @return string
     */
    public function showMetrics()
    {
        $terminal = $this->_qis->getTerminal();
        $metrics = $this->readMetrics();

        $show_metrics = ['loc', 'cr', 'csz', 'wmc'];

        $headers = $this->getHeader(['Class'], $show_metrics);
        $aligns = ['L', 'R', 'R', 'R', 'R'];
        $escapes = [
            'white-bgred' => $terminal->do_setaf(8) . $terminal->do_setab(1),
            'op' => $terminal->do_op(),
        ];

        $thresholds = $this->getThresholds();

        $rows = [];
        foreach ($metrics['packages'] as $package) {
            foreach ($package['classes'] as $class) {
                $row = [$class['fqname']];
                $this->addToRow($row, $class, $show_metrics, $thresholds, 'class.', $escapes);

                $rows[] = $row;
            }
        }

        $table = new Qi_Console_Tabular(
            $rows,
            array('headers' => $headers, 'cellalign' => $aligns, 'escapes' => $escapes)
        );

        $table->display();
        print "Use `qis metrics --class <search-term>` to show granular metrics per class.\n";
    }

    /**
     * Show metrics for class
     *
     * @param string $class
     * @return void
     */
    public function showMetricsForClass($targetClass)
    {
        $metrics = $this->readMetrics();
        $terminal = $this->_qis->getTerminal();

        $show_metrics = ['loc', 'ccn2', 'npath', 'hnt', 'hnd', 'hd', 'he', 'hb'];

        $headers = $this->getHeader(['Method'], $show_metrics);
        $aligns = array_merge(['L'], array_fill(0, 8, 'R'));

        // We need to track all the escape sequences for colors so we can pass
        // it into tabular so it can calc the right col widths
        $escapes = [
            'white-bgred' => $terminal->do_setaf(8) . $terminal->do_setab(1),
            'blue' => $terminal->do_setaf(4),
            'bold' => $terminal->do_bold(),
            'unbold' => $terminal->do_sgr0(),
            'op' => $terminal->do_op(),
        ];

        $thresholds = $this->getThresholds();

        $rows = [];
        foreach ($metrics['packages'] as $package) {
            foreach ($package['classes'] as $class) {
                // Match search term
                if (strpos($class['fqname'], $targetClass) === false) {
                    continue;
                }

                // Class row
                $name = $escapes['blue'] . $escapes['bold']
                    . $class['fqname']
                    . $escapes['unbold'] . $escapes['op'];
                $rows[] = array_merge(
                    [$name, $escapes['bold'] . $class['loc'] . $escapes['unbold']],
                    array_fill(0, 7, '')
                );

                // Class methods
                if (count($class['methods'])) {
                    foreach ($class['methods'] as $method) {
                        $row = ['*  ' . $method['name']];
                        $this->addToRow($row, $method, $show_metrics, $thresholds, 'method.', $escapes);
                        $rows[] = $row;
                    }
                } else {
                    $rows[] = array_merge(['*  (no methods)'], array_fill(0, 8, ''));
                }
            }
        }

        $table = new Qi_Console_Tabular(
            $rows,
            ['headers' => $headers, 'cellalign' => $aligns, 'escapes' => $escapes]
        );

        $table->display();
        print "Use `qis metrics --explain` to show an explanation of each metric.\n";
    }

    /**
     * Print out an explanation of the metrics
     *
     * @return void
     */
    public function showExplain()
    {
        print "An explanation of the metrics gathered and reported.\n";
        print "----------------------------------------------------\n";
        print "LOC: lines of code in file/method\n";
        print "CR: Code rank. A Google pagerank applied on packages and classes. Classes with a high value should be tested frequently.\n";
        print "CSZ: Class Size. Number of methods and properties of a class: CSZ = NOM + VARS. Measures the size of a class concerning operations and data.\n";
        print "NOM: Number of methods\n";
        print "VARS: Number of properties\n";
        print "WMC: Weighted Method Count. Sum of the complexities of all declared methods and constructors of class.\n";
        print "CCN2: Extended cyclomatic complexity number. Based on the number of branches in a code like if, for, foreach\n";
        print "NPATH: NPath Complexity. Number of acyclic execution paths through a method.\n";
        print "HNT: Halstead Length. Total number of operator occurrences and the total number of operand occurrences. HND = N1 + N2\n";
        print "HND: Halstead Vocabulary. Total number of unique operator and unique operand occurrences. HND = n1 + n2\n";
        print "HD: Halstead Difficulty. Difficulty of the program to write or understand, e.g. when doing code review. HD = (n1 / 2) * (N2 / n2)\n";
        print "HE: Halstead Effort. Aount of mental activity needed to translate the existing algorithm into implementation. HE = HV * HD\n";
        print "HB: Halstead Bugs. Estimated number of errors in the implementation HB = POW(HE, 2/3) / 3000\n";
    }

    /**
     * Get help message for this module
     *
     * @return string
     */
    public function getHelpMessage()
    {
        return "Gather metrics for project (runs pdepend)\n";
    }

    /**
     * Get extended help message
     *
     * @return string
     */
    public function getExtendedHelpMessage()
    {
        $out = $this->getHelpMessage() . "\n";

        $out .= "Usage: metrics [OPTIONS] [path]\n"
            . "This will run code metrics tool on the project files.\n\n"
            . "Valid Options:\n"
            . $this->_qis->getTerminal()->do_setaf(3)
            . "--results : Show results from last run\n"
            . "--class <name> : Show method metrics for class matching name\n"
            . "--explain : Show explanation of metrics\n"
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
        return false;
    }

    /**
     * Get summary for this module
     *
     * @param bool $short Return short message
     * @return string
     */
    public function getSummary($short = false)
    {
        if ($short) {
            return 'metrics';
        }

        return 'Metrics results';
    }

    /**
     * Get metrics for current status
     *
     * @param bool $onlyPrimary Return only the primary metric
     * @return array|float
     */
    public function getMetrics($onlyPrimary = false)
    {
        return 0.0;
    }

    /**
     * Get default ini settings for this module
     *
     * @return string
     */
    public static function getDefaultIni()
    {
        return "; Gather metrics on project classes and methods\n"
            . "metrics.command=metrics\n"
            . "metrics.class=" . get_called_class() . "\n"
            . "metrics.bin=pdepend\n"
            . "metrics.path=src\n"
            . "metrics.thresholds[class.wmc]=50\n"
            . "metrics.thresholds[method.ccn2]=10\n"
            . "metrics.thresholds[method.npath]=200\n"
            ;
    }
}
