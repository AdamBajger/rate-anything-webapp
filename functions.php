<?php
/**
 * Core Utility Functions for Rate Anything Application
 * 
 * This file contains the core business logic and data persistence layer.
 * All storage operations are abstracted through these functions to enable
 * easy migration to database storage in the future.
 * 
 * Key functions:
 * - loadYaml(): Load data from YAML storage
 * - saveYaml(): Persist data to YAML storage
 * - parseIdentifier(): Convert identifiers to human-readable names
 * - calculateStats(): Compute statistics from rating arrays
 * 
 * @package RateAnything
 */

/**
 * Load data from YAML file
 * 
 * This function serves as the primary data loading interface.
 * To migrate to database storage, replace this function with
 * database queries while maintaining the same return structure.
 * 
 * @param string $filename Path to YAML file
 * @return array Parsed data structure or empty array if file doesn't exist
 */
function loadYaml($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    
    $content = file_get_contents($filename);
    
    // Use PHP YAML extension (required, provided by Docker)
    if (!function_exists('yaml_parse')) {
        die('Error: PHP YAML extension is required. Please ensure it is installed.');
    }
    
    return yaml_parse($content);
}

/**
 * Save data to YAML file
 * 
 * This function serves as the primary data persistence interface.
 * To migrate to database storage, replace this function with
 * database operations while maintaining the same parameter structure.
 * 
 * @param string $filename Path to YAML file
 * @param array $data Data structure to persist
 * @return int|false Number of bytes written or false on failure
 */
function saveYaml($filename, $data) {
    if (!function_exists('yaml_emit')) {
        die('Error: PHP YAML extension is required. Please ensure it is installed.');
    }
    
    $yaml = yaml_emit($data);
    return file_put_contents($filename, $yaml);
}

/**
 * Parse identifier to human-readable format
 * 
 * Converts machine-readable identifiers to human-friendly display names
 * using configuration rules from config.yaml.
 * 
 * Example transformations:
 * - "item-001-coffee-machine" -> "Coffee Machine"
 * - "device-abc-water-cooler" -> "Water Cooler"
 * 
 * @param string $identifier Raw item identifier
 * @param array $config Configuration array with identifier parsing rules
 * @return string Formatted human-readable name
 */
function parseIdentifier($identifier, $config) {
    $regex = $config['identifier']['regex'] ?? '^[a-z]+-\\d+-(.*?)$';
    $format = $config['identifier']['format'] ?? 'title_case';
    $separator = $config['identifier']['separator'] ?? ' ';
    
    // Extract meaningful part using regex pattern
    if (preg_match('/' . $regex . '/i', $identifier, $matches)) {
        $name = $matches[1] ?? $identifier;
    } else {
        $name = $identifier;
    }
    
    // Replace dashes and underscores with configured separator
    $name = str_replace(['-', '_'], $separator, $name);
    
    // Apply formatting transformation
    switch ($format) {
        case 'title_case':
            $name = ucwords(strtolower($name));
            break;
        case 'upper_case':
            $name = strtoupper($name);
            break;
        case 'lower_case':
            $name = strtolower($name);
            break;
        case 'as_is':
        default:
            // Keep original formatting
            break;
    }
    
    return $name;
}

/**
 * Calculate statistics for a set of ratings
 * 
 * Computes comprehensive statistics including count, average, total,
 * minimum, and maximum values from an array of rating objects.
 * 
 * This function is designed to work with arrays of rating objects
 * where each object has a 'rating' key. For database migration,
 * this logic can be replaced with SQL aggregation functions.
 * 
 * @param array $ratings Array of rating objects with 'rating' keys
 * @return array Statistics array with keys: count, average, total, min, max
 */
function calculateStats($ratings) {
    if (empty($ratings)) {
        return [
            'count' => 0,
            'average' => 0,
            'total' => 0,
            'min' => 0,
            'max' => 0
        ];
    }
    
    // Extract rating values from rating objects
    $values = array_map(function($r) { return $r['rating']; }, $ratings);
    $count = count($values);
    $total = array_sum($values);
    $average = $total / $count;
    
    return [
        'count' => $count,
        'average' => round($average, 2),
        'total' => $total,
        'min' => min($values),
        'max' => max($values)
    ];
}
