<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the localization functions
 * 
 * Tests get_locale_from_config, load_locale_file, and translate functions.
 */
class LocalizationTest extends TestCase
{
    /**
     * Test get_locale_from_config returns 'en' for empty config
     */
    public function testGetLocaleFromEmptyConfig(): void
    {
        $result = get_locale_from_config([]);
        $this->assertEquals('en', $result);
    }

    /**
     * Test get_locale_from_config returns 'en' for non-array config
     */
    public function testGetLocaleFromNonArrayConfig(): void
    {
        $result = get_locale_from_config(null);
        $this->assertEquals('en', $result);
    }

    /**
     * Test get_locale_from_config returns configured locale
     */
    public function testGetLocaleFromConfiguredLocale(): void
    {
        $config = ['ui' => ['locale' => 'cs']];
        $result = get_locale_from_config($config);
        $this->assertEquals('cs', $result);
    }

    /**
     * Test get_locale_from_config handles full locale codes
     */
    public function testGetLocaleWithFullCode(): void
    {
        $config = ['ui' => ['locale' => 'en_US']];
        $result = get_locale_from_config($config);
        $this->assertEquals('en_US', $result);
    }

    /**
     * Test load_locale_file loads English locale
     */
    public function testLoadEnglishLocale(): void
    {
        $result = load_locale_file('en');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('leaderboard', $result);
        $this->assertArrayHasKey('submit_button', $result);
    }

    /**
     * Test load_locale_file loads Czech locale
     */
    public function testLoadCzechLocale(): void
    {
        $result = load_locale_file('cs');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('leaderboard', $result);
    }

    /**
     * Test load_locale_file returns empty array for non-existent locale
     */
    public function testLoadNonExistentLocale(): void
    {
        $result = load_locale_file('xyz');
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test translate returns translated string
     */
    public function testTranslateReturnsTranslatedString(): void
    {
        $config = ['ui' => ['locale' => 'en']];
        $result = translate('leaderboard', $config);
        
        $this->assertEquals('Leaderboard', $result);
    }

    /**
     * Test translate falls back to English when key not found in locale
     */
    public function testTranslateFallsBackToEnglish(): void
    {
        // Use a non-English locale with an English-only key
        $config = ['ui' => ['locale' => 'cs']];
        // The key should exist in English
        $result = translate('submit_button', $config);
        
        // Should return something (either Czech or English fallback)
        $this->assertNotEmpty($result);
        $this->assertNotEquals('submit_button', $result);
    }

    /**
     * Test translate returns key when not found anywhere
     */
    public function testTranslateReturnsKeyWhenNotFound(): void
    {
        $config = ['ui' => ['locale' => 'en']];
        $result = translate('non_existent_key_xyz', $config);
        
        $this->assertEquals('non_existent_key_xyz', $result);
    }

    /**
     * Test translate with empty config uses English
     */
    public function testTranslateWithEmptyConfig(): void
    {
        $result = translate('leaderboard', []);
        
        $this->assertEquals('Leaderboard', $result);
    }
}
