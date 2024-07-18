<?php

/**
 * Base Test Case class file
 *
 * @package Qis
 */

namespace Qis\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Base Test Case
 *
 * @uses PHPUnit_Framework_TestCase
 * @package Qis
 * @subpackage Tests
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class BaseTestCase extends TestCase
{
    /**
     * Storage of object being tested
     *
     * @var object
     */
    protected $object;
}
