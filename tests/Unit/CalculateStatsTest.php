<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the calculateStats function
 * 
 * Tests various scenarios for calculating statistics from rating arrays.
 */
class CalculateStatsTest extends TestCase
{
    /**
     * Test that empty ratings array returns zeros
     */
    public function testEmptyRatingsReturnsZeros(): void
    {
        $result = calculateStats([]);
        
        $this->assertEquals(0, $result['count']);
        $this->assertEquals(0, $result['average']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['min']);
        $this->assertEquals(0, $result['max']);
    }

    /**
     * Test single rating
     */
    public function testSingleRating(): void
    {
        $ratings = [
            ['rating' => 5, 'timestamp' => '2025-01-01 12:00:00']
        ];
        
        $result = calculateStats($ratings);
        
        $this->assertEquals(1, $result['count']);
        $this->assertEquals(5, $result['average']);
        $this->assertEquals(5, $result['total']);
        $this->assertEquals(5, $result['min']);
        $this->assertEquals(5, $result['max']);
    }

    /**
     * Test multiple ratings with same value
     */
    public function testMultipleRatingsSameValue(): void
    {
        $ratings = [
            ['rating' => 3, 'timestamp' => '2025-01-01 12:00:00'],
            ['rating' => 3, 'timestamp' => '2025-01-01 13:00:00'],
            ['rating' => 3, 'timestamp' => '2025-01-01 14:00:00']
        ];
        
        $result = calculateStats($ratings);
        
        $this->assertEquals(3, $result['count']);
        $this->assertEquals(3, $result['average']);
        $this->assertEquals(9, $result['total']);
        $this->assertEquals(3, $result['min']);
        $this->assertEquals(3, $result['max']);
    }

    /**
     * Test multiple ratings with different values
     */
    public function testMultipleRatingsDifferentValues(): void
    {
        $ratings = [
            ['rating' => 1, 'timestamp' => '2025-01-01 12:00:00'],
            ['rating' => 3, 'timestamp' => '2025-01-01 13:00:00'],
            ['rating' => 5, 'timestamp' => '2025-01-01 14:00:00']
        ];
        
        $result = calculateStats($ratings);
        
        $this->assertEquals(3, $result['count']);
        $this->assertEquals(3, $result['average']);
        $this->assertEquals(9, $result['total']);
        $this->assertEquals(1, $result['min']);
        $this->assertEquals(5, $result['max']);
    }

    /**
     * Test average rounding to 2 decimal places
     */
    public function testAverageRoundingToTwoDecimals(): void
    {
        $ratings = [
            ['rating' => 1, 'timestamp' => '2025-01-01 12:00:00'],
            ['rating' => 2, 'timestamp' => '2025-01-01 13:00:00'],
            ['rating' => 3, 'timestamp' => '2025-01-01 14:00:00']
        ];
        
        $result = calculateStats($ratings);
        
        // 6 / 3 = 2.0
        $this->assertEquals(2, $result['average']);
    }

    /**
     * Test fractional average
     */
    public function testFractionalAverage(): void
    {
        $ratings = [
            ['rating' => 1, 'timestamp' => '2025-01-01 12:00:00'],
            ['rating' => 2, 'timestamp' => '2025-01-01 13:00:00']
        ];
        
        $result = calculateStats($ratings);
        
        // 3 / 2 = 1.5
        $this->assertEquals(1.5, $result['average']);
    }

    /**
     * Test average with repeating decimal
     */
    public function testAverageWithRepeatingDecimal(): void
    {
        $ratings = [
            ['rating' => 1, 'timestamp' => '2025-01-01 12:00:00'],
            ['rating' => 1, 'timestamp' => '2025-01-01 13:00:00'],
            ['rating' => 2, 'timestamp' => '2025-01-01 14:00:00']
        ];
        
        $result = calculateStats($ratings);
        
        // 4 / 3 = 1.333... rounds to 1.33
        $this->assertEquals(1.33, $result['average']);
    }

    /**
     * Test negative ratings (if scale allows)
     */
    public function testNegativeRatings(): void
    {
        $ratings = [
            ['rating' => -1, 'timestamp' => '2025-01-01 12:00:00'],
            ['rating' => 0, 'timestamp' => '2025-01-01 13:00:00'],
            ['rating' => 1, 'timestamp' => '2025-01-01 14:00:00']
        ];
        
        $result = calculateStats($ratings);
        
        $this->assertEquals(3, $result['count']);
        $this->assertEquals(0, $result['average']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(-1, $result['min']);
        $this->assertEquals(1, $result['max']);
    }

    /**
     * Test floating point ratings
     */
    public function testFloatingPointRatings(): void
    {
        $ratings = [
            ['rating' => 2.5, 'timestamp' => '2025-01-01 12:00:00'],
            ['rating' => 3.5, 'timestamp' => '2025-01-01 13:00:00']
        ];
        
        $result = calculateStats($ratings);
        
        $this->assertEquals(2, $result['count']);
        $this->assertEquals(3, $result['average']);
        $this->assertEquals(6, $result['total']);
        $this->assertEquals(2.5, $result['min']);
        $this->assertEquals(3.5, $result['max']);
    }

    /**
     * Test large number of ratings
     */
    public function testLargeNumberOfRatings(): void
    {
        $ratings = [];
        for ($i = 0; $i < 1000; $i++) {
            $ratings[] = ['rating' => 5, 'timestamp' => '2025-01-01 12:00:00'];
        }
        
        $result = calculateStats($ratings);
        
        $this->assertEquals(1000, $result['count']);
        $this->assertEquals(5, $result['average']);
        $this->assertEquals(5000, $result['total']);
        $this->assertEquals(5, $result['min']);
        $this->assertEquals(5, $result['max']);
    }
}
