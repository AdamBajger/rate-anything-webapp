<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the bootstrap functions
 * 
 * Tests get_instance_id, config_file, and data_file functions.
 */
class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear any existing REQUEST data
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        // Clean up REQUEST data
        $_REQUEST = [];
    }

    /**
     * Test config_file returns correct path
     */
    public function testConfigFileReturnsCorrectPath(): void
    {
        $result = config_file('test');
        $this->assertStringEndsWith('/conf/test.yaml', $result);
    }

    /**
     * Test data_file returns correct path
     */
    public function testDataFileReturnsCorrectPath(): void
    {
        $result = data_file('test');
        $this->assertStringEndsWith('/data/test.yaml', $result);
    }

    /**
     * Test config_file with empty instance
     */
    public function testConfigFileWithEmptyInstance(): void
    {
        $result = config_file('');
        $this->assertStringEndsWith('/conf/.yaml', $result);
    }

    /**
     * Test data_file with empty instance
     */
    public function testDataFileWithEmptyInstance(): void
    {
        $result = data_file('');
        $this->assertStringEndsWith('/data/.yaml', $result);
    }
}
