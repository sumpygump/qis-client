<?php

/**
 * Qis Config class file
 *
 * @package Qis
 */

namespace Qis;

use StdClass;

/**
 * Qis Config class
 *
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Config
{
    /**
     * Storage of configuration data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Default configuration options
     *
     * @var array
     */
    protected $defaults = [
        'modules' => [],
    ];

    /**
     * Constructor
     *
     * @param string $filename Ini file to load
     * @return void
     */
    public function __construct($filename = null)
    {
        $this->data = $this->defaults;
        if ($filename !== null) {
            $this->loadIni($filename);
        }
    }

    /**
     * Load configuration data from array
     *
     * @param array $array Configuration data
     * @return void
     */
    public function loadArray($array)
    {
        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Load ini file
     *
     * @param string $filename Ini filename
     * @return void
     */
    protected function loadIni($filename)
    {
        $raw = parse_ini_file($filename, true);

        if (false === $raw) {
            throw new \Exception("Error reading config file '$filename'");
        }

        foreach ($raw as $key => $value) {
            if (is_array($value)) {
                $this->addArray($key, $value);
            } else {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * Add an array to the config data
     *
     * Parse out and nest sub items
     *
     * @param string $sectionName Config section
     * @param array $data Data to add
     * @return void
     */
    protected function addArray($sectionName, $data)
    {
        if (!isset($this->data[$sectionName])) {
            $this->data[$sectionName] = [];
        }

        $section = [];

        foreach ($data as $key => $value) {
            if (false !== strpos($key, '.')) {
                $pieces = explode('.', $key, 2);

                if (!$pieces[1]) {
                    // If missing the second half of key
                    // just make it the same as the first
                    $pieces[1] = $pieces[0];
                }

                if (!isset($section[$pieces[0]])) {
                    $section[$pieces[0]] = [];
                }
                $section[$pieces[0]][$pieces[1]] = $value;
            } else {
                $section[$key] = $value;
            }
        }

        $this->data[$sectionName] = $section;
    }

    /**
     * Get a configuration value
     *
     * @param mixed $var Name of setting
     * @param string $section Section name
     * @return mixed
     */
    public function get($var, $section = null)
    {
        //$value = new StdClass();
        $value = null;

        if (null == $section) {
            if (isset($this->data[$var])) {
                $value = $this->data[$var];
            }
        } else {
            if (isset($this->data[$section]) && isset($this->data[$section][$var])) {
                $value = $this->data[$section][$var];
            }
        }

        if (is_array($value)) {
            return (object) $value;
        }

        return $value;
    }

    /**
     * Set a value
     *
     * @param string $key The key name
     * @param mixed $value The value
     * @param mixed $sectionName The name of the section
     * @return void
     */
    public function set($key, $value, $sectionName = null)
    {
        $key = (string) $key;

        if (null === $sectionName) {
            if (is_array($value)) {
                $this->addArray($key, $value);
            } else {
                $this->data[$key] = $value;
            }
        } else {
            $this->addArray($sectionName, [$key => $value]);
        }
    }

    /**
     * Magic get method
     *
     * @param string $var Name of item
     * @return mixed
     */
    public function __get($var)
    {
        return $this->get($var);
    }
}
