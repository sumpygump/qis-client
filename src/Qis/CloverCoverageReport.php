<?php
/**
 * Clover Coverage Report class file
 *  
 * @package Qis
 */

namespace Qis;

use SebastianBergmann\PHPLOC\Analyser;
use Exception;

/**
 * Clover Coverage Report
 *
 * This class parses a clover coverage XML file and generates a report of the
 * findings in ASCII format.
 * 
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class CloverCoverageReport
{
    /**
     * Xml data
     * 
     * @var mixed
     */
    protected $_xml = null;

    /**
     * Report text
     * 
     * @var string
     */
    protected $_reportText = '';

    /**
     * Files
     * 
     * @var array
     */
    protected $_files = array();

    /**
     * Paths to ignore in the coverage report
     * 
     * @var array
     */
    protected $_ignorePaths = array();

    /**
     * Constructor
     * 
     * @param string $xmlFilename Filename to XML file
     * @param string $targetFile Optional PHP file for report
     * @param string $root Root directory to use for file paths
     * @param array $ignorePaths Paths (regex) to ignore in report
     * @return void
     */
    public function __construct($xmlFilename, $targetFile = '',
        $root = null, $ignorePaths = array())
    {
        if (!file_exists($xmlFilename)) {
            throw new CloverCoverageReportException(
                "File '$xmlFilename' not found or is not readable.", 64
            );
        }

        libxml_use_internal_errors(true);
        $this->_xml = simplexml_load_file($xmlFilename);
        if (false == $this->_xml) {
            $errors = array();
            foreach (libxml_get_errors() as $error) {
                $errors[] = trim($error->message)
                    . ' in file ' . trim($error->file) . ':' . $error->line;
            }
            libxml_clear_errors();
            throw new CloverCoverageReportException(implode("\n", $errors));
        }
        libxml_use_internal_errors(false);

        $this->_ignorePaths = $ignorePaths;

        if ($targetFile) {
            $this->generateFileAnalysis($targetFile, $root);
        } else {
            $this->generateReport($root);
        }
        $this->render();
    }

    /**
     * Run the logic to generate the report
     * 
     * @param string $root The root path to target
     * @return void
     */
    public function generateReport($root = null)
    {
        $this->gatherFileMetrics();
        if (null === $root) {
            $root = self::findCommonRoot(array_keys($this->_files));
        }

        // Find files in project that weren't included in the coverage report
        $files = self::rglob('*.php', 0, $root);
        foreach ($files as $file) {
            // TODO: have this use a defined tests folder in config file
            if (preg_match("/tests\//", $file)) {
                continue;
            }

            if (!empty($this->_ignorePaths)) {
                $ignoreRegex = implode('|', $this->_ignorePaths);
                if (preg_match("#" . $ignoreRegex . "#", $file)) {
                    continue;
                }
            }

            if (!isset($this->_files[$file])) {
                $sloc = $this->_getSloc($file);

                $this->_files[$file] = array(
                    'coveredstatements' => 0,
                    'statements'        => $sloc,
                );
            }
        }

        $timestamp = (int) $this->_xml->project['timestamp'];

        $this->addTitle();
        $this->append(
            "Coverage report generated " . date('Y-m-d H:i:s', $timestamp)
        );
        $this->append("Root: " . $root);
        $this->append(str_repeat("-", 64));
        $this->addFileMetrics($root);
        $this->append(str_repeat("-", 64));

        $this->append("Total Coverage: " . $this->getTotalCoverage() . "%");
        $this->append(str_repeat("-", 64));
    }

    /**
     * Get sloc (source lines of code for a file)
     * 
     * @param string $file Path to file
     * @return int
     */
    protected function _getSloc($file)
    {
        $analyser = new Analyser();
        $results = $analyser->countFiles(array($file), false);

        // lloc = Logical Lines of Code
        return $results['lloc'];
    }

    /**
     * Gather file metrics from XML
     *
     * Coverage XML will look like this, and we need to fetch metrics for each 
     * given file node:
     * <coverage>
     *     <project>
     *         <file>
     *             ...
     *         </file>
     *         <package>
     *            <file>
     *                ...
     *            </file>
     *         </package>
     *     </project>
     * </coverage>
     * 
     * @return void
     */
    public function gatherFileMetrics()
    {
        if (!isset($this->_xml->project)) {
            return false;
        }

        $this->gatherFileMetricsFromGroup($this->_xml->project->file);

        if (isset($this->_xml->project->package)) {
            foreach ($this->_xml->project->package as $package) {
                $this->gatherFileMetricsFromGroup($package->file);
            }
        }
    }

    /**
     * Gather file metrics from grouping in XML
     *
     * @param array $group File grouping in XML
     * @return void
     */
    public function gatherFileMetricsFromGroup($group)
    {
        foreach ($group as $file) {
            $name = (string) $file['name'];

            if (!empty($this->_ignorePaths)) {
                $ignoreRegex = implode('|', $this->_ignorePaths);
                if (preg_match("#" . $ignoreRegex. "#", $name)) {
                    continue;
                }
            }

            $coveredStatements = (int) $file->metrics['coveredstatements'];

            $fileMetric = array(
                'coveredstatements' => $coveredStatements,
                'statements' => (int) $file->metrics['statements'],
            );

            $this->_files[$name] = $fileMetric;
        }
    }

    /**
     * Add file metrics to report text
     * 
     * @param string $root The path root
     * @return void
     */
    public function addFileMetrics($root = '')
    {
        ksort($this->_files);
        $longestNameLength = 10;
        $largestLineCount  = 2;

        foreach ($this->_files as $name => $metrics) {
            $newFiles[str_replace($root, '', $name)] = $metrics;
        }
        $this->_files = $newFiles;

        foreach ($this->_files as $name => $metrics) {
            if (strlen($name) > $longestNameLength) {
                $longestNameLength = strlen($name);
            }

            if ($metrics['statements'] > $largestLineCount) {
                $largestLineCount = $metrics['statements'];
            }
        }

        $lineCountPad = strlen((string) $largestLineCount);

        foreach ($this->_files as $name => $metrics) {
            $line = str_pad($name, $longestNameLength);

            $coveredStatements = str_pad(
                $metrics['coveredstatements'], $lineCountPad, ' ', STR_PAD_LEFT
            );

            $statements = str_pad(
                $metrics['statements'], $lineCountPad, ' ', STR_PAD_LEFT
            );

            if ($metrics['statements'] != 0) {
                $percent = round(
                    $metrics['coveredstatements'] / $metrics['statements'] * 100
                );
            } else {
                $percent = 0;
            }

            $line .= " | " . $coveredStatements . ' / ' . $statements
                . " | " . str_pad($percent, 3, ' ', STR_PAD_LEFT) . "%"
                . "  " . $this->_bar($percent);

            $this->append($line);
        }
    }

    /**
     * Generate a file analysis for one file
     * 
     * @param string $file Path to file to analyze
     * @param string $root Root path
     * @return void
     */
    public function generateFileAnalysis($file, $root = null)
    {
        $this->gatherFileMetrics();

        if (empty($this->_files)) {
            return false;
        }

        if (null === $root) {
            $root = self::findCommonRoot(array_keys($this->_files));
        }

        // If file isn't in XML, prepend the root.
        if (!isset($this->_files[$file])) {
            $file = $root . $file;
        }

        // If it still isn't in the list, we don't know about it. abort.
        if (!isset($this->_files[$file])) {
            $this->append("No coverage information available\n for file $file");
            return false;
        }

        $stats = $this->gatherFileStatistics($file);

        $lines = file($file);

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            $prepend = str_pad($lineNumber, 5, ' ', STR_PAD_LEFT) . " ";

            if (isset($stats[$lineNumber])) {
                $prepend .= str_pad(
                    $stats[$lineNumber]->count, 8, ' ', STR_PAD_LEFT
                )
                    . " : ";
            } else {
                $prepend .= str_repeat(' ', 8) . " : ";
            }
            $this->append($prepend . rtrim($line));
        }
    }

    /**
     * Gather the line statistics for a given PHP file
     * 
     * @param string $filename Filename
     * @return array
     */
    public function gatherFileStatistics($filename)
    {
        $stats = array();

        $file = $this->findTargetFile($filename);

        if (null === $file) {
            return $stats;
        }

        foreach ($file->line as $line) {
            $lineObject = new StdClass();

            foreach ($line->attributes() as $key => $value) {
                $lineObject->{$key} = (string) $value;
            }

            $lineNumber = (int) $line['num'];

            $stats[$lineNumber] = $lineObject;
        }

        return $stats;
    }

    /**
     * Find a specific file's node in the XML
     *
     * @param string $filename Filename
     * @return string
     */
    public function findTargetFile($filename)
    {
        foreach ($this->_xml->project->file as $file) {
            if ((string) $file['name'] == $filename) {
                return $file;
            }
        }

        if (!isset($this->_xml->project->package)) {
            return null;
        }
    
        foreach ($this->_xml->project->package as $package) {
            foreach ($package->file as $file) {
                if ((string) $file['name'] == $filename) {
                    return $file;
                }
            }
        }
    }

    /**
     * Find common root from a list of file paths
     * 
     * @param array $list A list of file paths
     * @return string
     */
    public static function findCommonRoot(array $list)
    {
        $longest  = 0;
        $pathlist = array();
        $dirSep   = '/';

        if (count($list) == 1) {
            $root = implode(
                $dirSep,
                array_slice(explode($dirSep, reset($list)), 0, -1)
            ); 
            if (substr($root, -1) != $dirSep) {
                $root .= $dirSep;
            }

            return $root;
        }

        // Chunk each item into parts separated by dirSep
        foreach ($list as $item) {
            $pathparts  = explode($dirSep, $item);
            $pathlist[] = $pathparts;

            if (count($pathparts) > $longest) {
                $longest = count($pathparts);
            }
        }

        // Loop through each list item
        // Gradually add on each path part and check if all the paths are unique
        // As soon as the list is no longer unique,
        // stop and use the previous one
        $root = '';
        for ($i = 0; $i < $longest; $i++) {
            $common = array();
            foreach ($pathlist as $pathparts) {
                $path = implode($dirSep, array_slice($pathparts, 0, $i + 1));

                if (substr($path, -1) != $dirSep) {
                    $path .= $dirSep;
                }

                $common[] = $path;
            }

            if (count(array_unique($common)) > 1) {
                return $root;
            }
            $root = $common[0];
        }

        return $root;
    }

    /**
     * Create bar
     * 
     * @param mixed $percent The percent value
     * @return string
     */
    protected function _bar($percent)
    {
        $width = 10;
        $val   = $percent / $width;

        $text = "["
            . str_pad(str_repeat("*", $val), $width)
            . "]";

        return $text;
    }

    /**
     * Render the report
     * 
     * @return void
     */
    public function render()
    {
        echo $this->_reportText;
    }

    /**
     * Get total coverage from accumulated files
     *
     * @return float
     */
    public function getTotalCoverage()
    {
        $totalStatements   = 0;
        $coveredStatements = 0;

        foreach ($this->_files as $filename => $stats) {
            $totalStatements += $stats['statements'];

            $coveredStatements += $stats['coveredstatements'];
        }

        if ($totalStatements == 0) {
            return 0.0;
        }

        $totalCoveragePercentage = round(
            ($coveredStatements / $totalStatements) * 100, 2
        );

        return $totalCoveragePercentage;
    }

    /**
     * Get Total coverage percentage from the coverage XML
     *
     * This method doesn't respect the ignorePaths
     * 
     * @return float
     */
    public function getTotalCoverageFromCoverageXml()
    {
        $totalStatements = $this->_xml->project->metrics['statements'];

        $coveredStatements =
            $this->_xml->project->metrics['coveredstatements'];

        if ($totalStatements != 0) {
            $totalCoveragePercentage = round(
                ($coveredStatements / $totalStatements) * 100, 2
            );
        } else {
            $totalCoveragePercentage = 0;
        }

        return $totalCoveragePercentage;
    }

    /**
     * Add the title of the report (project name)
     * 
     * @return void
     */
    public function addTitle()
    {
        $project = $this->_xml->project;

        $name = (string) $project['name'];

        $this->append(str_repeat("-", 64));
        $this->append($name);
        $this->append(str_repeat("-", 64));
    }

    /**
     * Append text to the report text accumulator
     * 
     * @param mixed $text Text to append
     * @return void
     */
    public function append($text)
    {
        $this->_reportText .= $text . "\n";
    }

    /**
     * Recursive Glob
     * 
     * @param string $pattern Pattern
     * @param int $flags Flags to pass to glob
     * @param string $path Path
     * @return void
     */
    public static function rglob($pattern, $flags = 0, $path = '')
    {
        if (!$path && ($dir = dirname($pattern)) != '.') {
            if ($dir == '\\' || $dir == '/') {
                $dir = '';
            }
            return self::rglob(basename($pattern), $flags, $dir . '/');
        }

        $paths = glob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $files = glob($path . $pattern, $flags);

        foreach ($paths as $p) {
            $files = array_merge(
                $files, self::rglob($pattern, $flags, $p . '/')
            );
        }

        return $files;
    }
}

/**
 * CloverCoverageReportException
 *
 * @uses Exception
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class CloverCoverageReportException extends Exception
{
}
