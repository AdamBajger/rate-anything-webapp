<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the parseIdentifier function
 * 
 * Tests various regex patterns and identifier formats to ensure
 * proper parsing and name extraction.
 */
class ParseIdentifierTest extends TestCase
{
    /**
     * Test that parseIdentifier returns the original identifier when no regex is configured
     */
    public function testReturnsIdentifierWhenNoRegex(): void
    {
        $config = [];
        $identifier = 'test-item-123';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals($identifier, $result);
    }

    /**
     * Test that parseIdentifier returns the original identifier when regex is null
     */
    public function testReturnsIdentifierWhenRegexIsNull(): void
    {
        $config = ['identifier' => ['regex' => null]];
        $identifier = 'test-item-123';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals($identifier, $result);
    }

    /**
     * Test that parseIdentifier returns the original identifier when groups are empty
     */
    public function testReturnsIdentifierWhenGroupsEmpty(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^test-(.*)$/',
                'groups' => []
            ]
        ];
        $identifier = 'test-item-123';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals($identifier, $result);
    }

    /**
     * Test basic regex matching with a single capture group
     */
    public function testBasicRegexWithSingleGroup(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^item-\\d+-(.*?)$/',
                'groups' => [1]
            ]
        ];
        $identifier = 'item-001-coffee-machine';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals('Coffee Machine', $result);
    }

    /**
     * Test regex with URL pattern (stripping domain)
     */
    public function testUrlPatternStrippingDomain(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^(?:https?:\\/\\/)?(?:www\\.)?(?:example\\.com\\/)?(.*)$/',
                'groups' => [1]
            ]
        ];
        
        // Test with full URL
        $result = parseIdentifier('https://www.example.com/peru-light-roast', $config);
        $this->assertEquals('Peru Light Roast', $result);
        
        // Test with just path
        $result = parseIdentifier('peru-light-roast', $config);
        $this->assertEquals('Peru Light Roast', $result);
    }

    /**
     * Test regex with multiple capture groups
     */
    public function testMultipleCaptureGroups(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^([a-z]+)-(\\d+)-(.*)$/',
                'groups' => [1, 3]
            ]
        ];
        $identifier = 'coffee-123-ethiopian-blend';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals('Coffee Ethiopian Blend', $result);
    }

    /**
     * Test that underscores are converted to spaces and title-cased
     */
    public function testUnderscoresConvertedToSpaces(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^(.*)$/',
                'groups' => [1]
            ]
        ];
        $identifier = 'colombian_dark_roast';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals('Colombian Dark Roast', $result);
    }

    /**
     * Test that hyphens are converted to spaces and title-cased
     */
    public function testHyphensConvertedToSpaces(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^(.*)$/',
                'groups' => [1]
            ]
        ];
        $identifier = 'brazilian-medium-roast';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals('Brazilian Medium Roast', $result);
    }

    /**
     * Test that regex non-match returns original identifier
     */
    public function testRegexNonMatchReturnsOriginal(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^item-\\d+-(.*)$/',
                'groups' => [1]
            ]
        ];
        // This doesn't match the pattern (no number)
        $identifier = 'product-abc-test';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals($identifier, $result);
    }

    /**
     * Test coffee-specific URL pattern (coffeespot.com)
     */
    public function testCoffeespotUrlPattern(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^(?:https?:\\/\\/)?(?:www\\.)?(?:coffeespot\\.(?:com|cz)\\/)?(.*)$/',
                'groups' => [1]
            ]
        ];
        
        // Test with .com domain
        $result = parseIdentifier('https://www.coffeespot.com/ethiopia-yirgacheffe', $config);
        $this->assertEquals('Ethiopia Yirgacheffe', $result);
        
        // Test with .cz domain
        $result = parseIdentifier('https://coffeespot.cz/kenya-aa', $config);
        $this->assertEquals('Kenya Aa', $result);
        
        // Test without domain
        $result = parseIdentifier('guatemala-antigua', $config);
        $this->assertEquals('Guatemala Antigua', $result);
    }

    /**
     * Test that groups config as single value works
     */
    public function testGroupsAsSingleValue(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^item-(.*?)$/',
                'groups' => 1  // single value instead of array
            ]
        ];
        $identifier = 'item-test-product';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals('Test Product', $result);
    }

    /**
     * Test empty identifier
     */
    public function testEmptyIdentifier(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^(.*)$/',
                'groups' => [1]
            ]
        ];
        
        $result = parseIdentifier('', $config);
        
        $this->assertEquals('', $result);
    }

    /**
     * Test identifier with special characters
     */
    public function testIdentifierWithSpecialCharacters(): void
    {
        $config = [
            'identifier' => [
                'regex' => '/^(.*)$/',
                'groups' => [1]
            ]
        ];
        $identifier = 'café-arabica';
        
        $result = parseIdentifier($identifier, $config);
        
        $this->assertEquals('Café Arabica', $result);
    }
}
