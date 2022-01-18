<?php

/**
 * Clover Coverage Report class file
 *
 * @package Qis
 */

namespace Qis;

use SebastianBergmann\PHPLOC\Analyser;
use Exception;
use StdClass;

/**
 * Clover Coverage Report
 *
 * This class parses a clover coverage XML file and generates a report of the
 * findings in ASCII format.
 *
 * @package Qis
 * @author  Jansen Price <jansen.price@gmail.com>
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
     * @param  string $xmlFilename Filename to XML file
     * @param  string $targetFile  Optional PHP file for report
     * @param  string $root        Root directory to use for file paths
     * @param  array  $ignorePaths Paths (regex) to ignore in report
     * @return void
     */
    public function __construct(
        $xmlFilename,
        $targetFile = '',
        $root = null,
        $ignorePaths = array()
    ) {
        if (!file_exists($xmlFilename)) {
            throw new CloverCoverageReportException(
                "File '$xmlFilename' not found or is not readable.",
                64
            );
        }

        // Load the file
        // Turn on internal errors for libxml, so we can throw them as
        // exceptions in case of errors encountered while parsing XML
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

        // If targetFile is supplied, instead of outputting the overall stats,
        // display the file with line numbers and the number of times covered
        // for each line
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
     * @param  string $root The root path to target
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
            // Ignore the tests folder so it doesn't muddy up the report
            // TODO: have this use a defined tests folder in config file
            if (preg_match("/tests\//", $file)) {
                continue;
            }

            // User can supply paths to ignore, which uses simple regex to
            // filter filenames out
            if (!empty($this->_ignorePaths)) {
                $ignoreRegex = implode('|', $this->_ignorePaths);
                if (preg_match("#" . $ignoreRegex . "#", $file)) {
                    continue;
                }
            }

            // We want files that weren't in the coverage XML to also appear in
            // the report so we know which ones haven't been covered yet.
            if (!isset($this->_files[$file])) {
                $sloc = $this->_getSloc($file);

                $this->_files[$file] = array(
                    'coveredstatements' => 0,
                    'statements'        => $sloc,
                );
            }
        }

        $timestamp = (int) $this->_xml->project['timestamp'];

        // Generate the report by calling methods to add information
        // This is put into a buffer so it can be output
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
     * @param  string $file Path to file
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
     * @return bool
     */
    public function gatherFileMetrics()
    {
        if (!isset($this->_xml->project)) {
            return false;
        }

        // Handle <file> nodes
        $this->gatherFileMetricsFromGroup($this->_xml->project->file);

        // Handle <package> nodes
        if (isset($this->_xml->project->package)) {
            foreach ($this->_xml->project->package as $package) {
                $this->gatherFileMetricsFromGroup($package->file);
            }
        }

        return true;
    }

    /**
     * Gather file metrics from grouping in XML
     *
     * @param  array $group File grouping in XML
     * @return void
     */
    public function gatherFileMetricsFromGroup($group)
    {
        foreach ($group as $file) {
            $name = (string) $file['name'];

            // Use simple regex to filter out unwanted filenames
            if (!empty($this->_ignorePaths)) {
                $ignoreRegex = implode('|', $this->_ignorePaths);
                if (preg_match("#" . $ignoreRegex . "#", $name)) {
                    continue;
                }
            }

            // We will pull out covered statements and total statements for
            // each file
            $coveredStatements = (int) $file->metrics['coveredstatements'];

            $fileMetric = array(
                'coveredstatements' => $coveredStatements,
                'statements' => (int) $file->metrics['statements'],
            );

            // Save metrics by filename
            $this->_files[$name] = $fileMetric;
        }
    }

    /**
     * Add file metrics to report text
     *
     * @param  string $root The path root
     * @return void
     */
    public function addFileMetrics($root = '')
    {
        ksort($this->_files);

        // Strip out the long paths by replacing with a common root as supplied
        // in $root
        $newFiles = array();
        foreach ($this->_files as $name => $metrics) {
            $newFiles[str_replace($root, '', $name)] = $metrics;
        }
        $this->_files = $newFiles;

        // Calculate the column widths to accommodate the longest names
        // Start with some default assumptions for calculating the width of
        // columns
        $longestNameLength = 10;
        $largestLineCount  = 2;
        foreach ($this->_files as $name => $metrics) {
            if (strlen($name) > $longestNameLength) {
                $longestNameLength = strlen($name);
            }

            if ($metrics['statements'] > $largestLineCount) {
                $largestLineCount = $metrics['statements'];
            }
        }

        // Calculate some padding
        $lineCountPad = strlen((string) $largestLineCount);

        // Perform logic of laying out into tabular format
        foreach ($this->_files as $name => $metrics) {
            $line = str_pad($name, $longestNameLength);

            $coveredStatements = str_pad(
                $metrics['coveredstatements'],
                $lineCountPad,
                ' ',
                STR_PAD_LEFT
            );

            $statements = str_pad(
                $metrics['statements'],
                $lineCountPad,
                ' ',
                STR_PAD_LEFT
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
     * Shows the lines of code for the given file with line numbers and counts
     * for coverage for each line
     *
     * @param  string $file Path to file to analyze
     * @param  string $root Root path
     * @return bool
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

        // Store the contents of the files in an array for each line
        $lines = file($file);

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            $prepend = str_pad($lineNumber, 5, ' ', STR_PAD_LEFT) . " ";

            // Prepare the gutter to the left of each line, either showing the
            // number of times the statement was executed or else an empty
            // space
            if (isset($stats[$lineNumber])) {
                $prepend .= str_pad(
                    $stats[$lineNumber]->count,
                    8,
                    ' ',
                    STR_PAD_LEFT
                )
                    . " : ";
            } else {
                $prepend .= str_repeat(' ', 8) . " : ";
            }

            $this->append($prepend . rtrim($line));
        }

        return true;
    }

    /**
     * Gather the line statistics for a given PHP file
     *
     * @param  string $filename Filename
     * @return array
     */
    public function gatherFileStatistics($filename)
    {
        $stats = array();

        $file = $this->findTargetFile($filename);

        if (null === $file) {
            return $stats;
        }

        // Basically converts the XML statistics for a file into an array we can
        // traverse more easily, indexed by line number
        //
        // Example : <line num="185" type="stmt" count="6"/>
        // Possible attributes are:
        //  - num
        //  - type (stmt, method)
        //  - name (if method type, the name of the method)
        //  - crap (?)
        //  - count (count of executions for this line)
        //
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
     * @param  string $filename Filename
     * @return object|null
     */
    public function findTargetFile($filename)
    {
        // Look through <file> nodes
        foreach ($this->_xml->project->file as $file) {
            if ((string) $file['name'] == $filename) {
                return $file;
            }
        }

        if (!isset($this->_xml->project->package)) {
            return null;
        }

        // Look through <package> nodes
        foreach ($this->_xml->project->package as $package) {
            foreach ($package->file as $file) {
                if ((string) $file['name'] == $filename) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * Find common root from a list of file paths
     *
     * @param  array $list A list of file paths
     * @return string
     */
    public static function findCommonRoot(array $list)
    {
        if (count($list) == 1) {
            return self::commonRootOne(reset($list));
        }

        $longest = 0;
        $pathlist = array();

        // Chunk each item into parts separated by dirSep
        foreach ($list as $item) {
            $pathparts  = explode('/', $item);
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
                $path = implode('/', array_slice($pathparts, 0, $i + 1));

                if (substr($path, -1) != '/') {
                    $path .= '/';
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

    public static function commonRootOne($path)
    {
        $root = implode(
            '/',
            array_slice(explode('/', $path), 0, -1)
        );

        if (substr($root, -1) != '/') {
            $root .= '/';
        }

        return $root;
    }

    /**
     * Create a percentage bar with ascii
     *
     * @param  mixed $percent The percent value
     * @return string
     */
    protected function _bar($percent)
    {
        $width = 10;
        $val   = (int) ($percent / $width);

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
            ($coveredStatements / $totalStatements) * 100,
            2
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
                ($coveredStatements / $totalStatements) * 100,
                2
            );
        } else {
            $totalCoveragePercentage = 0.0;
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
        $name = 'Coverage';

        // Find the name from the XML, either in the project node, or the
        // package node if it exists
        if (isset($project['name'])) {
            $name = (string) $project['name'];
        } else {
            if (isset($project->package) && isset($project->package['name'])) {
                $name = (string) $project->package['name'];
            }
        }

        $this->append(str_repeat("-", 64));
        $this->append($name);
        $this->append(str_repeat("-", 64));
    }

    /**
     * Append text to the report text accumulator
     *
     * @param  mixed $text Text to append
     * @return void
     */
    public function append($text)
    {
        $this->_reportText .= $text . "\n";
    }

    /**
     * Recursive Glob
     *
     * @param  string $pattern Pattern
     * @param  int    $flags   Flags to pass to glob
     * @param  string $path    Path
     * @return array
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
                $files,
                self::rglob($pattern, $flags, $p . '/')
            );
        }

        return $files;
    }
}

/**
 * CloverCoverageReportException
 *
 * @uses    Exception
 * @package Qis
 * @author  Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class CloverCoverageReportException extends Exception
{
}
