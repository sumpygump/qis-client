<?php
/**
 * PdoSqlite Db class file
 *
 * @package Qi
 * @subpackage Db
 */

/**
 * Qi_Db_PdoSqlite
 *
 * Provides common functions for an interface to sqlite db.
 *
 * @package Qi
 * @subpackage Db
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 1.2
 */
class Qi_Db_PdoSqlite
{
    /**#@+
     * Constants for Errinfo()
     *
     * @var int
     */
    const ERRINFO_SQLSTATE_CODE = 0;
    const ERRINFO_ERROR_CODE    = 1;
    const ERRINFO_ERROR_MESSAGE = 2;
    /**#@-*/

    /**
     * Db Config settings
     *
     * @var array
     */
    protected $_cfg;

    /**
     * The database resource object
     *
     * @var object
     */
    protected $_conn;

    /**
     * Array of errors encountered
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Constructor
     *
     * @param array $dbcfg Database configuration data
     * @return void
     */
    public function __construct($dbcfg)
    {
        $cfgDefaults = array(
            'log'      => false,
            'log_file' => '',
            'dbfile'   => 'data.db3',
            'version'  => '3',
        );

        $this->_cfg = array_merge($cfgDefaults, (array) $dbcfg);

        if ($this->_cfg['version'] == '2') {
            $dsnPrefix = 'sqlite2';
        } else {
            $dsnPrefix = 'sqlite';
        }

        $this->_conn = new PDO($dsnPrefix . ':' . $this->_cfg['dbfile']);

        if (!$this->_conn) {
            echo "PdoSqlite connection error.\n";
        }
    }

    /**
     * Safely execute a sql query statement
     *
     * @param string $q The sql query statement
     * @param array $data Data to bind to query
     * @return array|bool The resulting data or false
     */
    public function safeQuery($q, $data = null)
    {
        // Log the sql statement if logging is enabled
        $this->log($q);
        if (!empty($data)) {
            $this->log(trim(print_r($data, 1)));
        }

        // Prepare the query
        $statement = $this->_conn->prepare($q);

        if (!$statement) {
            $this->_logPdoError($this->_conn->errorInfo());
        }

        // Execute the query
        if (!empty($data)) {
            $status = $statement->execute($data);
        } else {
            $status = $statement->execute();
        }

        if (!$status) {
            $this->_logPdoError($statement->errorInfo());
        }

        return $statement;
    }

    /**
     * Insert a row into a table
     *
     * @param string $table Name of table
     * @param array $data Associative array with data to insert
     * @return int|bool
     */
    public function insert($table, $data)
    {
        $keys         = array_keys($data);
        $placeholders = array_fill(0, count($keys), "?");

        $set = "(" . implode(',', $keys) . ") "
            . "VALUES (" . implode(',', $placeholders) . ")";

        $data = array_values($data);
        if ($this->safeInsert($table, $set, $data)) {
            return $this->_conn->lastInsertId();
        }

        return false;
    }

    /**
     * Safely insert rows into a table
     *
     * @param string $table The table name
     * @param string $set The set part of the query e.g. "VALUES (...)"
     * @param mixed $data Optional data to bind to prepared statement
     * @return bool Whether the statement executed successfully
     */
    public function safeInsert($table, $set, $data = null)
    {
        $q = "INSERT INTO $table $set";

        if ($r = $this->safeQuery($q, $data)) {
            return true;
        }

        return false;
    }

    /**
     * Safely update row or rows in a table
     *
     * @param string $table The table name
     * @param string $set The set part of the query e.g. "col='value'"
     * @param string $where The where clause
     * @param mixed $data Optional data to bind to prepared statement
     * @return bool Whether the statement executed successfully
     */
    public function safeUpdate($table, $set, $where, $data = null)
    {
        $q = "UPDATE $table SET $set WHERE $where";

        if ($r = $this->safeQuery($q, $data)) {
            return true;
        }

        return false;
    }

    /**
     * Update a row
     *
     * @param string $table Table name
     * @param array $data Associative array of data
     * @param string $where Where clause content
     * @return mixed
     */
    public function update($table, $data, $where)
    {
        $set = array();

        foreach ($data as $name => $value) {
            $set[] = "$name=?";
        }

        $set = implode(',', $set);

        $data = array_values($data);

        return $this->safeUpdate($table, $set, $where, $data);
    }

    /**
     * Safely delete a row or rows from a table
     *
     * @param string $table The table name
     * @param string $where The where clause
     * @param mixed $data Optional data to bind to prepared statement
     * @return bool Whether the statement executed successfully
     */
    public function safeDelete($table, $where, $data = null)
    {
        $q = "DELETE FROM $table WHERE $where";

        if ($r = $this->safeQuery($q, $data)) {
            return true;
        }

        return false;
    }

    /**
     * Safely get a thing from a table based on a criteria
     *
     * @param string $column The column name to extract
     * @param string $table The table name
     * @param string $where The where clause
     * @return mixed The data or false
     */
    public function safeField($column, $table, $where)
    {
        $q = "SELECT $column FROM $table WHERE $where";

        $r = $this->safeQuery($q);

        $row = $r->fetch(PDO::FETCH_NUM);

        if (isset($row[0])) {
            return $row[0];
        }

        return false;
    }

    /**
     * Safely extract column values from a row or rows
     *
     * @param string $column The thing to extract
     * @param string $table the table name
     * @param string $where The where clause
     * @return string|array A comma separated list of the values returned
     *                      or an empty array
     */
    public function safeColumn($column, $table, $where)
    {
        if (trim($where) == '') {
            $where = '1';
        }

        $q = "SELECT $column FROM $table WHERE $where";

        $rs = $this->getRows($q);

        if ($rs) {
            $out = array();
            foreach ($rs as $row) {
                $out[] = implode(",", $row);
            }
            return $out;
        }

        return array();
    }

    /**
     * Safely get a row from a table
     *
     * @param string $columns Comma separated list of columns to return
     * @param string $table The table name
     * @param string $where The where clause
     * @return array The row or an empty array
     */
    public function safeRow($columns, $table, $where)
    {
        if (trim($where) == '') {
            $where = '1';
        }

        $q = "SELECT $columns FROM $table WHERE $where LIMIT 1;";

        $rs = $this->getRow($q);

        if ($rs) {
            return $rs;
        }

        return array();
    }

    /**
     * Safely get rows from a table
     *
     * @param string $columns The columns to return
     * @param string $table The table name
     * @param string $where The where clause
     * @return array The rows or an empty array
     */
    public function safeRows($columns, $table, $where)
    {
        if (trim($where) == '') {
            $where = '1';
        }

        $q = "SELECT $columns FROM $table WHERE $where";

        $rs = $this->getRows($q);

        if ($rs) {
            return $rs;
        }

        return array();
    }

    /**
     * Get a count of rows
     *
     * @param string $table The table name
     * @param string $where The where clause
     * @return string The number of rows
     */
    public function safeCount($table, $where)
    {
        if (trim($where) == '') {
            $where = '1';
        }

        return $this->getThing(
            "SELECT count(*) FROM $table WHERE $where"
        );
    }

    /**
     * Safely alter a table
     *
     * @param string $table The table name
     * @param string $alter The alter part of statement e.g. "ADD COLUMN ... "
     * @return bool Whether the statement executed successfully
     */
    public function safeAlter($table, $alter)
    {
        $q = "ALTER TABLE $table $alter";

        if ($r = $this->safeQuery($q)) {
            return true;
        }

        return false;
    }

    /**
     * Safely optimize a table
     *
     * @param string $table The table name
     * @return bool Whether the statement executed successfully
     */
    public function safeOptimize($table)
    {
        $this->log("Optimize is not available for sqlite.", "Warning");

        return false;
    }

    /**
     * Safely repair a table
     *
     * @param string $table The table name
     * @return bool Whether the statement executed successfully
     */
    public function safeRepair($table)
    {
        $this->log("Repair is not available for sqlite.", "Warning");

        return false;
    }

    /**
     * Fetch a value for a specific condition
     *
     * @param string $col The column to return
     * @param string $table The table name
     * @param string $key The column to test for the condition
     * @param string $val The value to test for in column $key
     * @return mixed The first row matching the query or false
     */
    public function fetch($col, $table, $key, $val)
    {
        $queryString = "SELECT $col FROM $table WHERE $key = "
            . $this->_conn->quote($val) . " LIMIT 1;";

        $statement = $this->safeQuery($queryString);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!isset($row[$col])) {
            return null;
        }

        return $row[$col];
    }

    /**
     * Execute a sql query and return the first resulting row
     *
     * @param string $query The sql query statement
     * @param array $data Data to bind to query
     * @param int $indices The array indices returned
     *                     (SQLITE_NUM, SQLITE_ASSOC, SQLITE_BOTH)
     * @return array|bool The resulting row or null
     */
    public function getRow($query, $data = null, $indices=PDO::FETCH_ASSOC)
    {
        if ($r = $this->safeQuery($query, $data)) {
            return $r->fetch($indices);
        }

        return false;
    }

    /**
     * Execute a sql query and return the resulting rows
     *
     * @param string $query The sql query statement
     * @param array $data Data to bind to query
     * @param int $indices The array indices returned
     *                     (SQLITE_NUM, SQLITE_ASSOC, SQLITE_BOTH)
     * @return array|bool The resulting rows or false
     */
    public function getRows($query, $data = null, $indices=PDO::FETCH_ASSOC)
    {
        if ($r = $this->safeQuery($query, $data)) {
            return $r->fetchAll($indices);
        }

        return false;
    }

    /**
     * Execute a sql query and return the first column in the resulting row
     *
     * @param string $query The sql query statement
     * @return mixed The resulting thing or null
     */
    public function getThing($query)
    {
        if (!$r = $this->safeQuery($query)) {
            return null;
        }

        $row = $r->fetch(PDO::FETCH_NUM);

        if (!isset($row[0])) {
            return null;
        }

        return $row[0];
    }

    /**
     * Return values of one column from multiple rows in an num indexed array
     *
     * @param string $query The sql statement
     * @param array $data Data elements to bind to query
     * @return void
     */
    public function getThings($query, $data = array())
    {
        $rs = $this->getRows($query, $data, PDO::FETCH_NUM);

        $out = array();

        if (!$rs) {
            return $out;
        }

        foreach ($rs as $a) {
            $out[] = $a[0];
        }

        return $out;
    }

    /**
     * Get a count of rows meeting a criteria
     *
     * @param string $table The table name
     * @param string $where The where clause
     * @return string The resulting number of rows
     */
    public function getCount($table, $where)
    {
        if (trim($where) == '') {
            $where = 1;
        }

        return $this->getThing(
            "SELECT count(*) FROM $table WHERE $where"
        );
    }

    /**
     * Do a safe query, return lastInsertId if successful.
     *
     * @param string $q The sql statement
     * @return mixed The insert id or error message
     */
    public function doSafeQuery($q)
    {
        if ($result = $this->safeQuery($q)) {
            return $this->_conn->lastInsertId();
        }

        $err = $this->_conn->errorInfo();
        return $err[0] . ": " . $err[2];
    }

    /**
     * Escape string for sqlite use
     *
     * @param string $string The string to be escaped
     * @return string The sanitized string
     * @deprecated You should just use quote()
     */
    public function escape($string)
    {
        if (!function_exists('sqlite_escape_string')) {
            return str_replace("'", "''", $string);
        }

        return sqlite_escape_string($string);
    }

    /**
     * Magic call method to pass down to db object
     *
     * @param string $method Method name
     * @param array $args Arguments
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_conn, $method), $args);
    }

    /**
     * Set an error message
     *
     * @param string $errorMessage The error message
     * @return object Self (fluid interface)
     */
    public function setError($errorMessage)
    {
        $this->_errors = array_merge($this->_errors, array($errorMessage));
        return $this;
    }

    /**
     * Get errors
     *
     * @return array An array of error messages that have been set
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Log message to file
     *
     * @param mixed $message Message to log
     * @param mixed $label Label
     * @return void
     */
    public function log($message, $label = null)
    {
        if (!$this->_cfg['log']) {
            return false;
        }

        if (null === $label) {
            $label = date('Y-m-d H:i:s') . ' ' . getmypid();
        }

        $this->_prepareLogDir();

        file_put_contents(
            $this->_cfg['log_file'],
            $label . " ==> " . $message . "\n",
            FILE_APPEND
        );
    }

    /**
     * Prepare log dir
     *
     * Ensures that the directory exists and is writable and makes the log file 
     * writable by all. This allows for web requests and command line scripts 
     * to access writing to the file, since it will usually be different users.
     *
     * @return void
     * @throws \Exception
     */
    protected function _prepareLogDir()
    {
        $logFile = $this->_cfg['log_file'];

        // Determine if log dir is writable or whether we can write to the log file
        if (!is_writable(dirname($logFile))
            || (file_exists($logFile) && !is_writable($logFile))
        ) {
            throw new \Exception("Cannot write to log file '" . $logFile . "'");
        }

        // If the file doesn't exist yet, then the current user is the one that 
        // will create it. This user should make it writable by others.
        if (!file_exists($logFile)) {
            file_put_contents($logFile, '');
            // Make the log file writable by others
            chmod($logFile, 0777);
        }
    }

    /**
     * Log a PDO Error
     * 
     * @param array $err PDO Error Info array
     * @return void
     */
    protected function _logPdoError($err)
    {
        // Log the error
        $this->log(
            $err[self::ERRINFO_ERROR_MESSAGE],
            'ERROR ' . $err[self::ERRINFO_SQLSTATE_CODE]
        );

        // Add to the Db Object error list
        $errorMessage = $err[self::ERRINFO_SQLSTATE_CODE]
            . ": " . $err[self::ERRINFO_ERROR_MESSAGE];

        $this->setError($errorMessage);
        throw new Qi_Db_PdoSqliteException(
            $errorMessage, $err[self::ERRINFO_ERROR_CODE]
        );
    }
}

/**
 * Qi_Db_PdoSqliteException
 *
 * @uses Exception
 * @package Qi
 * @subpackage Db
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qi_Db_PdoSqliteException extends Exception
{
}
