<?php
/**
 * Qi_Db_PdoSqlite Test class file
 *
 * @package Qis
 */

/**
 * @see Qi_Console_PdoSqlite
 */
require_once 'Qi/Db/PdoSqlite.php';

/**
 * Qi_Console_PdoSqlite Test class
 *
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Qi_Db_PdoSqliteTest extends BaseTestCase
{
    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp()
    {
        $cfg = array(
            'dbfile'   => 'test.db3',
            'log'      => true,
            'log_file' => 'testdb.log',
        );

        $this->_createObject($cfg);
        $this->_createTestTable();
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown()
    {
        @unlink('test.db3');
        @unlink('testdb.log');

        if (file_exists('test.db2')) {
            @unlink('test.db2');
        }

        if (file_exists('data.db3')) {
            @unlink('data.db3');
        }
    }

    /**
     * Create object
     *
     * @param array $cfg Config
     * @return void
     */
    protected function _createObject($cfg)
    {
        $this->_object = new Qi_Db_PdoSqlite($cfg);
    }

    /**
     * Constructor test with empty array
     *
     * @return void
     */
    public function testConstructEmptyArgs()
    {
        $cfg = array();

        $this->_createObject($cfg);
        $this->assertTrue(is_object($this->_object));
    }

    /**
     * TestConstructSqliteVersionTwo
     *
     * This fails bcause it can't find the driver for sqlite2
     *
     * @expectedException PDOException
     * @return void
     */
    public function testConstructSqliteVersionTwo()
    {
        $cfg = array(
            'dbfile'  => 'test.db2',
            'version' => '2',
        );

        $this->_createObject($cfg);
        $this->assertTrue(is_object($this->_object));
    }

    /**
     * Test initializing connection to db file to a folder without write
     * permissions
     *
     * @expectedException PDOException
     * @return void
     */
    public function testConstructToFolderWithoutWritePerms()
    {
        $cfg = array(
            'dbfile' => '/etc/testsqlite.db3',
        );

        $this->_createObject($cfg);
        $this->assertFalse(is_object($this->_object));
    }

    /**
     * testConstructWithBadParams
     *
     * @expectedException PDOException
     * @return void
     */
    public function testConstructWithBadParams()
    {
        $cfg = array(
            'dbfile' => ':mysql:dbname=testdb;unix_socket=/path/to/socket',
        );

        $this->_createObject($cfg);
    }

    /**
     * Test create table
     *
     * @return void
     */
    public function testCreateTable()
    {
        $sql = "select * from users";

        $r = $this->_object->getRows($sql);

        $this->assertEquals(array(), $r);
    }

    /**
     * Create table once it was already called
     *
     * @return void
     * @expectedException Qi_Db_PdoSqliteException already 1
     */
    public function testCreateTableTwice()
    {
        $this->_createTestTable();
    }

    /**
     * Test safe query with data binding
     *
     * @return void
     */
    public function testSafeQueryWithDataBinding()
    {
        $sql = "insert into users (name, email) values (?, ?)";

        $data = array('jansen', 'jansen@test.com');

        $expected = array(
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $r = $this->_object->safeQuery($sql, $data);

        $sql = "select * from users";

        $actual = $this->_object->getRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * testInvalidQuery
     *
     * @expectedException Qi_Db_PdoSqliteException such
     * @return void
     */
    public function testInvalidQuery()
    {
        $sql = "SELECT * FROM foobar WHERE email=?";

        $data = array();

        $result = $this->_object->safeQuery($sql, $data);
    }

    /**
     * Test invalid statement
     *
     * @return void
     */
    public function testInvalidStatement()
    {
    }

    /**
     * Test safe insert
     *
     * @return void
     */
    public function testSafeInsert()
    {
        $set = "('name', 'email') values ('jansen', 'jansen@test.com')";

        $expected = array(
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $r = $this->_object->safeInsert('users', $set);

        $sql = "select * from users";

        $actual = $this->_object->getRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * Test safe insert with error
     *
     * @expectedException Qi_Db_PdoSqliteException syntax 1
     * @return void
     */
    public function testSafeInsertWithError()
    {
        $set = "'name', 'email') values ('jansen', 'jansen@test.com')";

        $expected = false;

        $r = $this->_object->safeInsert('users', $set);
        $this->assertFalse($r);

        $sql = "select * from users";

        $actual = $this->_object->getRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * Test insert
     *
     * @return void
     */
    public function testInsert()
    {
        $data = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $expected = array(
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $r = $this->_object->insert('users', $data);
        $this->assertEquals('1', $r);

        $sql = "select * from users";
        
        $actual = $this->_object->getRows($sql);

        $this->assertEquals($expected, end($actual));
    }

    /**
     * Test calling insert with a column that doesn't exist on table
     *
     * @return void
     * @expectedException Qi_Db_PdoSqliteException flaxx 1
     */
    public function testInsertWithExtraColumn()
    {
        $data = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
            'flaxx' => 'none',
        );

        $r = $this->_object->insert('users', $data);
        $this->assertFalse($r);
    }

    /**
     * Test safe update
     *
     * @return void
     */
    public function testSafeUpdate()
    {
        $response = $this->_object->safeUpdate(
            'users', "name='orihah'", 'id=?',
            array(1)
        );

        $this->assertTrue($response);
    }

    /**
     * Test update
     *
     * @return void
     */
    public function testUpdate()
    {
        $data = array(
            'name' => 'orihah',
            'email' => 'orihah@test.com',
        );

        $response = $this->_object->update('users', $data, 'id=?', array(1));
        $this->assertTrue($response);

    }

    /**
     * Test safe delete
     *
     * @return void
     */
    public function testSafeDelete()
    {
        $response = $this->_object->safeDelete('users', 'id=?', array(1));
        $this->assertTrue($response);
    }

    /**
     * Test safe field
     *
     * @return void
     */
    public function testSafeField()
    {
        $this->_populateTestData();
        $name = $this->_object->safeField('name', 'users', "id='1'");

        $this->assertEquals('jansen', $name);
    }

    /**
     * Test safe field without quotes
     *
     * @return void
     */
    public function testSafeFieldWithoutQuotes()
    {
        $this->_populateTestData();
        $name = $this->_object->safeField('name', 'users', "id=1");

        $this->assertEquals('jansen', $name);
    }

    /**
     * Test safe field multiple columns
     *
     * @return void
     */
    public function testSafeFieldMultipleColumns()
    {
        $this->_populateTestData();
        $result = $this->_object->safeField('name,email', 'users', "id=1");

        $this->assertEquals('jansen', $result);
    }

    /**
     * Test safe field no results
     *
     * @return void
     */
    public function testSafeFieldNoResults()
    {
        $this->_populateTestData();
        $result = $this->_object->safeField('name', 'users', "id=22");

        $this->assertFalse($result);
    }

    /**
     * Test safe column
     *
     * @return void
     */
    public function testSafeColumn()
    {
        $this->_populateTestData();

        $expected = array(
            'jansen',
        );

        $result = $this->_object->safeColumn('name', 'users', '');

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe column no results
     *
     * @return void
     */
    public function testSafeColumnNoResults()
    {
        $this->_populateTestData();

        $expected = array();

        $result = $this->_object->safeColumn('name', 'users', 'id=22');

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe column more than one result
     *
     * @return void
     */
    public function testSafeColumnMoreThanOneResult()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $expected = array(
            'jansen',
            'orihah',
        );

        $result = $this->_object->safeColumn('name', 'users', '');

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe row
     *
     * @return void
     */
    public function testSafeRow()
    {
        $this->_populateTestData();

        $result = $this->_object->safeRow('name,email', 'users', 'id=1');

        $expected = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe row no results
     *
     * @return void
     */
    public function testSafeRowNoResults()
    {
        $this->_populateTestData();

        $result = $this->_object->safeRow('name,email', 'users', 'id=22');

        $this->assertEquals(array(), $result);
    }

    /**
     * Test safe row only one
     *
     * @return void
     */
    public function testSafeRowOnlyOne()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $result = $this->_object->safeRow('name,email', 'users', '');

        $expected = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe rows
     *
     * @return void
     */
    public function testSafeRows()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $result = $this->_object->safeRows('name,email', 'users', '');

        $expected = array(
            array(
                'name' => 'jansen',
                'email' => 'jansen@test.com',
            ),
            array(
                'name' => 'orihah',
                'email' => 'orihah@test.com',
            ),
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe rows no records
     *
     * @return void
     */
    public function testSafeRowsNoRecords()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $result = $this->_object->safeRows('name,email', 'users', 'id > 22');

        $expected = array();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test safe count
     *
     * @return void
     */
    public function testSafeCount()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $result = $this->_object->safeCount('users', '');

        $this->assertEquals(2, $result);
    }

    /**
     * Test safe alter
     *
     * @return void
     */
    public function testSafeAlter()
    {
        $alter = 'ADD COLUMN active integer';

        $result = $this->_object->safeAlter('users', $alter);

        $statement = $this->_object->safeQuery("PRAGMA table_info('users')");

        $pragma = $statement->fetchAll(PDO::FETCH_ASSOC);

        // This is the expected third column (row in pragma array)
        $expected = array(
            'cid'        => '3',
            'name'       => 'active',
            'type'       => 'integer',
            'notnull'    => '0',
            'dflt_value' => '',
            'pk'         => '0',
        );

        $this->assertEquals($expected, $pragma[3]);
    }

    /**
     * testSafeAlterError
     *
     * @expectedException Qi_Db_PdoSqliteException Cannot
     * @return void
     */
    public function testSafeAlterError()
    {
        $alter = 'ADD COLUMN active integer PRIMARY KEY';

        $result = $this->_object->safeAlter('users', $alter);
    }

    /**
     * Test safe optimize
     *
     * @return void
     */
    public function testSafeOptimize()
    {
        $result = $this->_object->safeOptimize('users');

        $this->assertFalse($result);
    }

    /**
     * Test safe repair
     *
     * @return void
     */
    public function testSafeRepair()
    {
        $result = $this->_object->safeRepair('users');

        $this->assertFalse($result);
    }

    /**
     * Test fetch
     *
     * @return void
     */
    public function testFetch()
    {
        $this->_populateTestData();

        $result = $this->_object->fetch('name', 'users', 'id', 1);
        $this->assertEquals('jansen', $result);
    }

    /**
     * Test fetch no results
     *
     * @return void
     */
    public function testFetchNoResults()
    {
        $this->_populateTestData();

        $result = $this->_object->fetch('name', 'users', 'id', 22);

        $this->assertNull($result);
    }

    /**
     * Test fetch with alias
     *
     * @return void
     */
    public function testFetchWithAlias()
    {
        $this->_populateTestData();

        $result = $this->_object->fetch('name as n', 'users', 'id', 1);

        $this->assertNull($result);
    }

    /**
     * Test get row no results
     *
     * @return void
     */
    public function testGetRowNoResults()
    {
        $this->_populateTestData();
        $result = $this->_object->getRow('SELECT * FROM users WHERE id=22');

        $this->assertFalse($result);
    }

    /**
     * Test get row with bind data
     *
     * @return void
     */
    public function testGetRowWithBindData()
    {
        $data = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $expected = array(
            'id'    => '1',
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        $r = $this->_object->insert('users', $data);

        $q = "select * from users where id=?";

        $data = array('1');

        $r = $this->_object->getRow($q, $data);
        $this->assertEquals($expected, $r);
    }

    /**
     * Test get things
     *
     * @return void
     */
    public function testGetThings()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $query = 'SELECT name,email FROM users';

        $expected = array(
            'jansen',
            'orihah',
        );

        $result = $this->_object->getThings($query);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test get things no results
     *
     * @return void
     */
    public function testGetThingsNoResults()
    {
        $this->_populateMoreTestData();

        $query = 'SELECT name,email FROM users WHERE id > 22';

        $expected = array();

        $result = $this->_object->getThings($query);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test get count
     *
     * @return void
     */
    public function testGetCount()
    {
        $this->_populateTestData();
        $this->_populateMoreTestData();

        $expected = 2;

        $result = $this->_object->getCount('users', '');

        $this->assertEquals($expected, $result);
    }

    /**
     * Test do safe query
     *
     * @return void
     */
    public function testDoSafeQuery()
    {
        $this->_populateTestData();

        $query = "INSERT INTO users (name,email) "
            . "VALUES ('george', 'george@test.com')";

        $result = $this->_object->doSafeQuery($query);

        $this->assertEquals(2, $result);
    }

    /**
     * Test escape
     *
     * @return void
     */
    public function testEscape()
    {
        $result = $this->_object->escape("Don't blink");

        $expected = "Don''t blink";

        $this->assertEquals($expected, $result);
    }

    /**
     * Test magic call
     *
     * @return void
     */
    public function testMagicCall()
    {
        $result = $this->_object->errorInfo();

        $this->assertEquals(3, count($result));
    }

    /**
     * Test set error
     *
     * @return void
     */
    public function testSetError()
    {
        $this->_object->setError('');

        $result = $this->_object->getErrors();

        $this->assertEquals(array(''), $result);
    }

    /**
     * Create test table
     *
     * @return void
     */
    protected function _createTestTable()
    {
        $sql = "create table users (
            'id' integer primary key,
            'name' text,
            'email' text
        );";

        $this->_object->safeQuery($sql);
    }

    /**
     * Populate test data
     *
     * @return void
     */
    protected function _populateTestData()
    {
        $data = array(
            'name'  => 'jansen',
            'email' => 'jansen@test.com',
        );

        return $this->_object->insert('users', $data);
    }

    /**
     * Populate more test data
     *
     * @return void
     */
    protected function _populateMoreTestData()
    {
        $data = array(
            'name'  => 'orihah',
            'email' => 'orihah@test.com',
        );

        return $this->_object->insert('users', $data);
    }
}
