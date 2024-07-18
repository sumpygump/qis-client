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
 * @uses \PHPUnit\Framework\TestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
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
