<?php
/**
 * Utils test class file
 *
 * @package Qis
 */

/**
 * @see Utils
 */
require_once 'Utils.php';

/**
 * Utils test class
 *
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@nerdery.com>
 * @version $Id$
 */
class UtilsTest extends BaseTestCase
{
    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp()
    {
        mkdir('testrglob');
        touch('testrglob/foo1.php');
        touch('testrglob/foo2.txt');
        mkdir('testrglob/wunk');
        touch('testrglob/wunk/soody.php');
        touch('testrglob/wunk/farse.txt');
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown()
    {
        unlink('testrglob/wunk/farse.txt');
        unlink('testrglob/wunk/soody.php');
        rmdir('testrglob/wunk');
        unlink('testrglob/foo2.txt');
        unlink('testrglob/foo1.php');
        rmdir('testrglob');
    }

    /**
     * Test rglob
     *
     * @return void
     */
    public function testRglob()
    {
        $result = Utils::rglob('*.txt');

        $expected = array(
            'testrglob/foo2.txt',
            'testrglob/wunk/farse.txt',
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test rglob with dirname check
     *
     * @return void
     */
    public function testRglobWithDirnameCheck()
    {
        $result = Utils::rglob('../*.txt');

        $expected = array(
            '../readme.txt',
            '../tests/testrglob/foo2.txt',
            '../tests/testrglob/wunk/farse.txt',
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test rglob with directory in pattern
     *
     * @return void
     */
    public function testRglobWithDirectoryInPattern()
    {
        $result = Utils::rglob('foo/*.txt');

        $expected = array();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test rglob with just wild card
     *
     * @return void
     */
    public function testRglobWithJustWildcard()
    {
        $result = Utils::rglob('*');

        $this->assertTrue(count($result) > 0);
    }

    /**
     * Test rglob with root
     *
     * @return void
     */
    public function testRglobWithRoot()
    {
        $result = Utils::rglob('reqwrewqrewqrewqrewqrewqrewq.txt', 0, '/var');
        $this->assertEquals(array(), $result);
    }

    /**
     * Test rglob with root in pattern
     *
     * @return void
     */
    public function testRglobWithRootInPattern()
    {
        $result = Utils::rglob('/foo.txt');
        $this->assertEquals(array(), $result);
    }

    /**
     * Test find common root
     *
     * @return void
     */
    public function testFindCommonRoot()
    {
        $list = array(
            'foo/bar/baz/wunk/cat/x',
            'foo/bar/baz/y',
            'foo/bar/baz/z',
            'foo/bar/baz/can/a',
            'foo/bar/baz/zork/z/z/z/z/z',
        );

        $expected = 'foo/bar/baz/';

        $result = Utils::findCommonRoot($list);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test find common root only last char differs
     *
     * @return void
     */
    public function testFindCommonRootOnlyLastCharDiffers()
    {
        $list = array(
            'abcdef',
            'abcdex',
        );

        $expected = 'abcde';

        $result = Utils::findCommonRoot($list);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test find common root empty array
     *
     * @return void
     */
    public function testFindCommonRootEmptyArray()
    {
        $list = array();

        $expected = '';

        $result = Utils::findCommonRoot($list);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test find common root string
     *
     * @return void
     */
    public function testFindCommonRootString()
    {
        $list = 'muahaha';

        $expected = 'muahaha';

        $result = Utils::findCommonRoot($list);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test find common root one item
     *
     * @return void
     */
    public function testFindCommonRootOneItem()
    {
        $list = array(
            'foo/bar/',
        );

        $expected = 'foo/bar/';

        $result = Utils::findCommonRoot($list);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test find common root two items
     *
     * @return void
     */
    public function testFindCommonRootTwoItems()
    {
        $list = array(
            'abcdef',
            'axxxxx',
        );

        $expected = 'a';

        $result = Utils::findCommonRoot($list);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test find common root object
     *
     * @return void
     */
    public function testFindCommonRootObject()
    {
        $list = new StdClass();

        $list->foo = 'way';

        $expected = $list;

        $result = Utils::findCommonRoot($list);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test find common root with ints
     *
     * @return void
     */
    public function testFindCommonRootWithInts()
    {
        $list = array(
            1113,
            1112,
        );

        $expected = '111';

        $result = Utils::findCommonRoot($list);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test find common root mixed array
     *
     * @return void
     */
    public function testFindCommonRootMixedArray()
    {
    }
}
