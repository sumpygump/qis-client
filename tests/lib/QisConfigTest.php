<?php
/**
 * QisConfig Test class file 
 *
 * @package Qis
 */

/**
 * @see QisConfig
 */
require_once 'QisConfig.php';

/**
 * QisConfig Test class
 * 
 * @uses BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class QisConfigTest extends BaseTestCase
{
    /**
     * Setup before each test
     * 
     * @return void
     */
    public function setUp()
    {
        $this->_object = new QisConfig();
    }

    /**
     * Tear down after each test
     * 
     * @return void
     */
    public function tearDown()
    {
    }

    /**
     * Construct object and read ini file
     * 
     * @return void
     */
    public function testConstructWithFile()
    {
        $file     = '/tmp/qis-config.ini';
        $contents = "; QIS configuration file v1.0.8\n"
            . "project_name=test786T464";
        file_put_contents($file, $contents);

        $this->_object = new QisConfig($file);

        $this->assertEquals('test786T464', $this->_object->project_name);
        unlink($file);
    }

    /**
     * Test load array
     *
     * @return void
     */
    public function testLoadArray()
    {
        $array = array(
            'project_name' => 'test893##re',
            'secret' => 'buffalo',
        );

        $this->_object->loadArray($array);

        $this->assertEquals('test893##re', $this->_object->project_name);
        $this->assertEquals('buffalo', $this->_object->secret);
    }

    /**
     * Test load ini with sections
     *
     * @return void
     */
    public function testLoadIniWithSections()
    {
        $file     = '/tmp/qis-config.ini';
        $contents = "; QIS configuration file v1.0.8\n"
            . "project_name=test786T464\n"
            . "\n"
            . "[images]\n"
            . "width=640\n"
            . "height=480\n";
        file_put_contents($file, $contents);

        $this->_object = new QisConfig($file);

        $this->assertEquals('640', $this->_object->images->width);

        unlink($file);
    }

    /**
     * Test add array
     *
     * @return void
     */
    public function testAddArray()
    {
        $data = array(
            'colony' => array(
                'population' => 144450,
                'altitude'   => 26.5,
                'location'   => 'AA23',
            ),
        );

        $this->_object->loadArray($data);

        $this->assertEquals('AA23', $this->_object->colony->location);
    }

    /**
     * Setting an array overwrites a scalar value that had the same name
     *
     * This seems like a bug, but there isn't a good way around it
     * 
     * @return void
     */
    public function testAddArrayOverwrite()
    {
        $data = array(
            'colony' => 'original',
            'colony' => array(
                'population' => 144450,
                'altitude'   => 26.5,
                'location'   => 'AA23',
            ),
        );

        $this->_object->loadArray($data);

        $this->assertNotEquals('original', $this->_object->colony);
        $this->assertEquals('AA23', $this->_object->colony->location);
    }

    /**
     * Test add array with sub sections
     *
     * @return void
     */
    public function testAddArrayWithSubSections()
    {
        $data = array(
            'colony' => array(
                'population' => 144450,
                'altitude'   => 26.5,
                'location.lat'   => 44.545144,
                'location.lng'   => 68.128004,
            ),
        );

        $this->_object->loadArray($data);
        $this->assertEquals(44.545144, $this->_object->colony->location['lat']);
    }

    /**
     * Handle a mistake in the ini names
     *
     * When this happens the subkey is just a duplicate
     * of the first half of the key
     * 
     * @return void
     */
    public function testAddArrayWithValueEndingInDot()
    {
        $data = array(
            'colony' => array(
                'population'   => 144450,
                'altitude'     => 26.5,
                'location.lat' => 44.545144,
                'location.'    => 68.128004,
            ),
        );

        $this->_object->loadArray($data);
        $this->assertEquals(
            68.128004, $this->_object->colony->location['location']
        );
    }

    /**
     * Test add array with multiple dots in key name
     *
     * @return void
     */
    public function testAddArrayWithMultipleDotsInKeyName()
    {
        $data = array(
            'colony' => array(
                'population'             => 144450,
                'altitude'               => 26.5,
                'location.lat'           => 44.545144,
                'location.lng.estimated' => 68.128004,
            ),
        );

        $expected = new StdClass();

        $expected->lat = 44.545144;

        $expected->{'lng.estimated'} = 68.128004;

        $this->_object->loadArray($data);
        $this->assertEquals(
            $expected, $this->_object->get('location', 'colony')
        );
    }

    /**
     * Test set with section name
     *
     * @return void
     */
    public function testSetWithSectionName()
    {
        $expected = new StdClass();

        $expected->name = 'Geordi';

        $this->_object->set('name', 'Geordi', 'characters');
        $this->assertEquals($expected, $this->_object->characters);
    }
}
