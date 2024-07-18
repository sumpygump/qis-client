<?php

/**
 * Qis Command History test class file
 *
 * @package Qis
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qis\Tests\Command;

use Qis\Tests\BaseTestCase;
use Qis\Command\History;
use Qis\ModuleInterface;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Qis Command History Test cases
 *
 * @uses \Qis\Tests\BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class HistoryTest extends BaseTestCase
{
    public $qis;

    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        $path = realpath('.') . DIRECTORY_SEPARATOR . '.qis';
        mkdir($path);

        $args     = new Qi_Console_ArgV([]);
        $terminal = new Qi_Console_Terminal();

        $this->qis = new Qis($args, $terminal);

        $settings = [];

        $this->object = new History($this->qis, $settings);
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    public function tearDown(): void
    {
        $path = realpath('.') . DIRECTORY_SEPARATOR . '.qis';
        if (file_exists($path)) {
            passthru("rm -rf $path");
        }
    }

    /**
     * Run execute on the object and return the buffered output and status
     *
     * @param Qis_Console_ArgV $args Arguments
     * @return array
     */
    protected function execute($args)
    {
        ob_start();
        $status = $this->object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        return [$result, $status];
    }

    /**
     * Get name should return the default command name
     *
     * @return void
     */
    public function testGetName()
    {
        $name = History::getName();

        $this->assertEquals('history', $name);
    }

    /**
     * Initialize doesn't do anything, but it should be available
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->object->initialize();

        $this->assertTrue(true);
    }

    public function testGetHelpMessage()
    {
        $result = $this->object->getHelpMessage();
        $this->assertEquals("Show history data for modules\n", $result);
    }

    public function testGetExtendedHelpMessage()
    {
        $result = $this->object->getExtendedHelpMessage();
        $this->assertStringContainsString("Show history data for modules\n", $result);
        $this->assertStringContainsString("Usage: history [module]", $result);
        $this->assertStringContainsString("This will display a history of", $result);
    }

    /**
     * Test execute with no history
     *
     * @return void
     */
    public function testExecuteNoHistory()
    {
        $args = new Qi_Console_ArgV([]);
        list($result, $status) = $this->execute($args);
        $this->assertEquals("No history to display.\n", $result);
    }

    /**
     * Test execute with some history
     *
     * @return void
     */
    public function testExecuteHistory()
    {
        $this->makeHistory();
        $args = new Qi_Console_ArgV([]);

        list($result, $status) = $this->execute($args);

        $this->assertStringNotContainsString("No history to display.\n", $result);
        $this->assertStringContainsString(
            "|  2024-07-17 18:32:18  |  cs      |        |  PASS    |  "
            . "Codingstandard error level: 0.63%  |  99.37   |\n",
            $result
        );
        $this->assertStringContainsString(
            "|  2024-07-17 18:32:28  |  test    |        |  PASS    |  "
            . "Test: PASS                         |  151     |\n",
            $result
        );
    }

    /**
     * Test execute with module arg
     *
     * @return void
     */
    public function testExecuteHistoryWithArg()
    {
        $this->makeHistory();
        $args = new Qi_Console_ArgV(['qis', 'history', 'cs']);

        list($result, $status) = $this->execute($args);

        $this->assertStringNotContainsString("No history to display.\n", $result);
        $this->assertStringContainsString(
            "|  2024-07-17 18:32:18  |  cs      |        |  PASS    |  "
            . "Codingstandard error level: 0.63%  |  99.37   |\n",
            $result
        );
        $this->assertStringNotContainsString(
            "|  2024-07-17 18:32:28  |  test    |        |  PASS    |  "
            . "Test: PASS                         |  151     |\n",
            $result
        );
    }

    public function makeHistory()
    {
        $history = '[
            {
                "module": "cs",
                "args": "",
                "date": "2024-07-17 18:32:18",
                "status": true,
                "summary": "Codingstandard error level: 0.63%",
                "metric": "99.37",
                "metrics": "{\"SLOC\":10675,\"Comments\":3625,\"Errors\":30,\"Warnings\":120,\"Error Level\":\"0.63%\"}"
            },
            {
                "module": "test",
                "args": "",
                "date": "2024-07-17 18:32:28",
                "status": true,
                "summary": "Test: PASS",
                "metric": "151",
                "metrics": "{\"tests\":\"151\",\"assertions\":\"230\",\"failures\":\"0\",\"errors\":\"0\"}"
            }
        ]';
        file_put_contents('.qis/history.json', $history);
    }
}
