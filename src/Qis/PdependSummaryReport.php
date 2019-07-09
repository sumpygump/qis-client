<?php
/**
 * Pdepend Summary Report class file
 *
 * @package Qis
 */

namespace Qis;

class PdependSummaryReport
{
    /**
     * Xml data
     *
     * @var mixed
     */
    protected $_xml = null;

    protected $data = [];

    public function __construct($xmlFilename)
    {
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
            throw new \Exception(implode("\n", $errors));
        }
        libxml_use_internal_errors(false);
    }

    public function parse()
    {
        if (!isset($this->_xml->package)) {
            return false;
        }

        $this->data['packages'] = [];
        foreach ($this->_xml->package as $package) {
            $this->data['packages'][] = $this->parsePackage($package);
        }

        return $this->data;
    }

    public function parsePackage($packageXml)
    {
        $package = [];
        foreach ($packageXml->attributes() as $key => $value) {
            $package[$key] = (string) $value;
        }

        $package['classes'] = [];
        foreach ($packageXml->class as $classXml) {
            $package['classes'][] = $this->parseClass($classXml);
        }

        return $package;
    }

    public function parseClass($classXml)
    {
        $class = [];
        foreach ($classXml->attributes() as $key => $value) {
            $class[$key] = (string) $value;
        }

        $class['file'] = (string) $classXml->file;
        $class['methods'] = [];
        foreach ($classXml->method as $methodXml) {
            $class['methods'][] = $this->parseMethod($methodXml);
        }

        return $class;
    }

    public function parseMethod($methodXml)
    {
        $method = [];
        foreach ($methodXml->attributes() as $key => $value) {
            $method[$key] = (string) $value;
            if (is_numeric($method[$key])) {
                $method[$key] = sprintf("%.3f", $method[$key]);
            }
            if (substr($method[$key], -3, 3) == '000') {
                $method[$key] = (int) $method[$key];
            }
        }

        return $method;
    }
}
