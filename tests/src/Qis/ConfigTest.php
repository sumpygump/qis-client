<?php

/**
 * QisConfig Test class file
 *
 * @package Qis
 */

namespace Qis\Tests;

use Qis\Tests\BaseTestCase;
use Qis\Config;
use StdClass;

/**
 * QisConfig Test class
 *
 * @uses \Qis\Tests\BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class ConfigTest extends BaseTestCase
{
    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->object = new Config();
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

        $this->object = new Config($file);

        $this->assertEquals('test786T464', $this->object->project_name);
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

        $this->object->loadArray($array);

        $this->assertEquals('test893##re', $this->object->project_name);
        $this->assertEquals('buffalo', $this->object->secret);
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

        $this->object = new Config($file);

        $this->assertEquals('640', $this->object->images->width);

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

        $this->object->loadArray($data);

        $this->assertEquals('AA23', $this->object->colony->location);
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
        $data = [
            'colony' => 'original',
        ];

        $data['colony'] = [
            'population' => 144450,
            'altitude'   => 26.5,
            'location'   => 'AA23',
        ];

        $this->object->loadArray($data);

        $this->assertNotEquals('original', $this->object->colony);
        $this->assertEquals('AA23', $this->object->colony->location);
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

        $this->object->loadArray($data);
        $this->assertEquals(44.545144, $this->object->colony->location['lat']);
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

        $this->object->loadArray($data);
        $this->assertEquals(
            68.128004,
            $this->object->colony->location['location']
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

        $this->object->loadArray($data);
        $this->assertEquals(
            $expected,
            $this->object->get('location', 'colony')
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

        $this->object->set('name', 'Geordi', 'characters');
        $this->assertEquals($expected, $this->object->characters);
    }

    /**
     * Test attempting to get a value that is not set in the config
     *
     * @return void
     */
    public function testGetValueNotSet()
    {
        $value = $this->object->foobar;
        $this->assertEquals(null, $value);
    }
}
