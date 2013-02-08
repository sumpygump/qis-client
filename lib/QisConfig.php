<?php
/**
 * Qis Config class file
 *
 * @package Qis
 */

/**
 * Qis Config class
 *
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class QisConfig
{
    /**
     * Storage of configuration data
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Default configuration options
     *
     * @var array
     */
    protected $_defaults = array(
        'modules' => array(),
    );

    /**
     * Constructor
     *
     * @param string $filename Ini file to load
     * @return void
     */
    public function __construct($filename = null)
    {
        $this->_data = $this->_defaults;
        if ($filename !== null) {
            $this->_loadIni($filename);
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
    protected function _loadIni($filename)
    {
        $raw = parse_ini_file($filename, true);

        foreach ($raw as $key => $value) {
            if (is_array($value)) {
                $this->_addArray($key, $value);
            } else {
                $this->_data[$key] = $value;
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
    protected function _addArray($sectionName, $data)
    {
        if (!isset($this->_data[$sectionName])) {
            $this->_data[$sectionName] = array();
        }

        $section = array();

        foreach ($data as $key => $value) {
            if (false !== strpos($key, '.')) {
                $pieces = explode('.', $key, 2);

                if (!$pieces[1]) {
                    // If missing the second half of key
                    // just make it the same as the first
                    $pieces[1] = $pieces[0];
                }

                if (!isset($section[$pieces[0]])) {
                    $section[$pieces[0]] = array();
                }
                $section[$pieces[0]][$pieces[1]] = $value;
            } else {
                $section[$key] = $value;
            }
        }

        $this->_data[$sectionName] = $section;
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
        $value = new StdClass();

        if (null == $section) {
            if (isset($this->_data[$var])) {
                $value = $this->_data[$var];
            }
        } else {
            if (isset($this->_data[$section])
                && isset($this->_data[$section][$var])
            ) {
                $value = $this->_data[$section][$var];
            }
        }

        if (is_array($value)) {
            return (object) $value;
        }

        return $value;;
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
                $this->_addArray($key, $value);
            } else {
                $this->_data[$key] = $value;
            }
        } else {
            $this->_addArray($sectionName, array($key => $value));
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
