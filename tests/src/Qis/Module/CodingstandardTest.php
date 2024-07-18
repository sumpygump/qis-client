<?php

/**
 * Test codingstandard module
 *
 * @package Qis
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Qis\Tests\Module;

use Qis\Tests\BaseTestCase;
use Qis\Module\Codingstandard;
use Qis\Module\CodingStandardException;
use Qis\Qis;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * Mock Qis Module coding standard
 *
 * @uses Qis\Module\Codingstandard
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class MockQisModuleCodingstandard extends Codingstandard
{
    /**
     * Get standard
     *
     * @return string
     */
    public function getStandard()
    {
        return $this->standard;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Check version
     *
     * @return bool
     */
    public function publicCheckVersion()
    {
        return $this->checkVersion();
    }

    public function publicRunCodeSniff($paths, $options = [])
    {
        return $this->runCodeSniff($paths, $options);
    }
}

/**
 * MockQisModuleCodingstandardErrorLevel
 *
 * @uses MockQisModuleCodingstandard
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class MockQisModuleCodingstandardErrorLevel extends MockQisModuleCodingstandard
{
    /**
     * Get Project Summary
     *
     * @return array
     */
    public function getProjectSummary()
    {
        return ['error_level' => 4];
    }
}

/**
 * Codingstandard Module Test class
 *
 * @uses \Qis\Tests\BaseTestCase
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 */
class CodingstandardTest extends BaseTestCase
{
    /**
     * Example file for sniffing
     *
     * @var string
     */
    public $sampleFile = 'example.php';

    /**
     * Setup before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        $path = realpath('.') . DIRECTORY_SEPARATOR . '.qis';
        mkdir($path);

        $this->createObject();
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
        @unlink($this->sampleFile);
    }

    /**
     * Test constructor with no arguments
     *
     * @return void
     */
    public function testConstructorWithNoArguments()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage("Too few arguments");
        $this->object = new Codingstandard();
    }

    /**
     * testConstructorWithoutSecondArgument
     *
     * @return void
     */
    public function testConstructorWithoutSecondArgument()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage("Too few arguments");
        $this->object = new Codingstandard(
            $this->getDefaultQisObject()
        );
    }

    /**
     * Test constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $settings = [];

        $this->object = new Codingstandard(
            $this->getDefaultQisObject(),
            $settings
        );

        $this->assertInstanceOf('Qis\Module\Codingstandard', $this->object);
    }

    /**
     * Test constructor set defaults
     *
     * @return void
     */
    public function testConstructorSetDefaults()
    {
        $settings = [
            'standard' => 'Foox',
            'path'     => 'vvvvv',
        ];

        $this->object = new MockQisModuleCodingstandard(
            $this->getDefaultQisObject(),
            $settings
        );

        $this->assertEquals('Foox', $this->object->getStandard());
        $this->assertEquals('vvvvv', $this->object->getPath());
    }

    /**
     * Test initialize
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->createObject(false);

        $this->object->initialize();

        $path = realpath('.') . DIRECTORY_SEPARATOR . '.qis/codingstandard/';
        // This should have created files in the directory.
        // assert they exist
        $this->assertTrue(file_exists($path));
        $this->assertTrue(file_exists($path . 'cs.db3'));
        $this->assertTrue(file_exists($path . 'db.log'));
    }

    /**
     * testExecuteNoArguments
     *
     * @return void
     */
    public function testExecuteNoArguments()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->expectExceptionMessage("Too few arguments");
        $this->object->execute();
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $args = new Qi_Console_ArgV([]);

        ob_start();
        $this->object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('Running Codingstandard module', $result);
        $this->assertStringContainsString(
            'Sniffing code with \'PSR2\' standard...',
            $result
        );
        $this->assertStringContainsString('Writing results to db...', $result);
        $this->assertStringContainsString('Codingstandard results:', $result);
    }

    /**
     * testCheckVersion
     *
     * @return void
     */
    public function testCheckVersion()
    {
        $this->expectException(CodingStandardException::class);

        $this->object->setOption('bin', 'ffffffff');
        $this->object->publicCheckVersion();
    }

    /**
     * Test check version cannot detect version
     *
     * @return void
     */
    public function testCheckVersionCannotDetectVersion()
    {
        // The : command will output nothing and return status 0
        // This means no version output will be found so checkVersion will
        // return false
        $this->object->setOption('bin', ':');
        $result = $this->object->publicCheckVersion();

        $this->assertFalse($result);
    }

    /**
     * Test check version not found match
     *
     * @return void
     */
    public function testCheckVersionNotFoundMatch()
    {
        // The ls command doesn't output the version in the same format as
        // phpcs
        $this->object->setOption('bin', 'ls');
        $this->expectException(\Qis\Module\CodingStandardException::class);
        $result = $this->object->publicCheckVersion();

        $this->assertFalse($result);
    }

    /**
     * Test execute with path not found
     *
     * @return void
     */
    public function testExecuteWithPathNotFound()
    {
        $args = [
            'cs',
            'foo',
            'margarine',
        ];
        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("Path `margarine' not found", $result);
    }

    /**
     * Test execute with valid path
     *
     * @return void
     */
    public function testExecuteWithValidPath()
    {
        $args = [
            'cs',
            'foo',
            'src/Qis/Command/AllTest.php',
        ];
        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("Sniffing code with", $result);
        $this->assertStringContainsString("Codingstandard results:", $result);
    }

    /**
     * Test execute with multiple paths
     *
     * @return void
     */
    public function testExecuteWithMultiplePaths()
    {
        $args = [
            'cs',
            'foo',
            'grab,bag,hag',
        ];
        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("Path `grab,bag,hag' not found", $result);
    }

    /**
     * Test execute with list command
     *
     * @return void
     */
    public function testExecuteWithListCommand()
    {
        $args = [
            'cs',
            '--list',
        ];

        $args = new Qi_Console_ArgV($args);

        ob_start();
        $this->object->execute($args);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringNotContainsString("PHP CODE SNIFFER REPORT SUMMARY", $result);
        $this->assertStringContainsString("Running Codingstandard module task...", $result);
    }

    /**
     * Test get help message
     *
     * @return void
     */
    public function testGetHelpMessage()
    {
        $message = $this->object->getHelpMessage();
        $this->assertStringContainsString('Run coding standard validation', $message);
    }

    /**
     * Test get extended help message
     *
     * @return void
     */
    public function testGetExtendedHelpMessage()
    {
        $message = $this->object->getExtendedHelpMessage();
        $this->assertStringContainsString('Usage: cs', $message);
        $this->assertStringContainsString('Valid Options:', $message);
    }

    /**
     * Test get summary
     *
     * @return void
     */
    public function testGetSummary()
    {
        $summary = $this->object->getSummary();

        $this->assertStringNotContainsString('Codingstandard error level', $summary);
    }

    /**
     * Test get summary short
     *
     * @return void
     */
    public function testGetSummaryShort()
    {
        $summary = $this->object->getSummary(true);

        $this->assertStringContainsString('Codingstandard: No data.', $summary);
    }

    /**
     * Test get default ini
     *
     * @return void
     */
    public function testGetDefaultIni()
    {
        $defaultIni = $this->object->getDefaultIni();

        $this->assertStringContainsString('; Module to run codesniffs', $defaultIni);
        $this->assertStringContainsString('codingstandard.standard=', $defaultIni);
    }

    /**
     * Test get status
     *
     * @return void
     */
    public function testGetStatus()
    {
        $status = $this->object->getStatus();

        $this->assertFalse($status);
    }

    /**
     * Test get status error
     *
     * @return void
     */
    public function testGetStatusError()
    {
        $settings = [
            'standard' => 'PSR2',
            'path'     => '.',
        ];

        $this->object = new MockQisModuleCodingstandardErrorLevel(
            $this->getDefaultQisObject([]),
            $settings
        );

        $status = $this->object->getStatus();

        $this->assertFalse($status);
    }

    public function testRunCodeSniff()
    {
        $this->makeSampleFile('<?php class funbar() { const LMNOP = 1; };');

        ob_start();
        $this->object->publicRunCodeSniff([$this->sampleFile]);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('FOUND 8 ERRORS AND 1 WARNING', $result);
    }

    public function testRunCodeSniffFileNoExist()
    {
        ob_start();
        $this->object->publicRunCodeSniff(['broccoli.php']);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("File 'broccoli.php' doesn't exist.", $result);
    }

    public function testRunCodeSniffFileVerbose()
    {
        $this->createObject(true, ['ignore' => 'vendor'], ['qis', 'cs', '-v']);
        $this->makeSampleFile('<?php class funbar() { const LMNOP = 1; };');

        ob_start();
        $this->object->publicRunCodeSniff([$this->sampleFile]);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("phpcs --standard=PSR2", $result);
        $this->assertStringContainsString("--ignore='vendor'", $result);
        $this->assertStringContainsString('FOUND 8 ERRORS AND 1 WARNING', $result);
    }

    public function testRunCodeSniffFileDisplayFile()
    {
        $this->makeSampleFile('<?php class funbar() { const LMNOP = 1; };');

        ob_start();
        $this->object->publicRunCodeSniff([$this->sampleFile]);
        $this->object->displayList();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('|  file         |  errors  |  warnings  |', $result);
        $this->assertStringContainsString('|  example.php  |       8  |         1  |', $result);
    }

    public function testGetSummaryWithErrors()
    {
        $this->makeSampleFile('<?php class funbar() { const LMNOP = 1; };');

        ob_start();
        $this->object->publicRunCodeSniff([$this->sampleFile]);
        $result = ob_get_contents();
        ob_end_clean();

        $result = $this->object->getSummary(true);

        $this->assertStringContainsString('level: 0.13%', $result);
    }

    /**
     * Create object
     *
     * @param bool $initialize Whether to initialize
     * @param Qi_Console_ArgV $args Arguments to pass to object
     * @return Codingstandard
     */
    protected function createObject($initialize = true, $settings = null, $args = [])
    {
        if (null == $settings) {
            $settings = [
                'standard' => 'PSR2',
                'path'     => '.',
            ];
        }

        $this->object = new MockQisModuleCodingstandard(
            $this->getDefaultQisObject($args),
            $settings
        );

        if ($initialize) {
            $this->object->initialize();
        }
    }

    /**
     * Get default qis object
     *
     * @param Qi_Console_ArgV $args Arguments
     * @return Qis
     */
    protected function getDefaultQisObject($args = [])
    {
        $rules = [
            'arg:action' => 'Subcommand',
            'help|h'     => 'Show help',
            'direct|d'   => 'Show results directly in console',
            'verbose|v'  => 'Include more verbose output',
            'quiet|q'    => 'Print less messages',
            'version'    => 'Show version',
            'no-color'   => 'Don\'t use color output',
        ];
        $argv     = new Qi_Console_ArgV($args, $rules);
        $terminal = new Qi_Console_Terminal();

        Qis::$exit = false;
        return new Qis($argv, $terminal);
    }

    protected function makeSampleFile($text)
    {
        file_put_contents($this->sampleFile, $text);
    }
}
