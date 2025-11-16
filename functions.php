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
 * NOTE: This function requires the PHP YAML extension. If not available in
 * the environment, install it via: apt-get install php-yaml or pecl install yaml
 * 
 * @param string $filename Path to YAML file
 * @return array Parsed data structure or empty array if file doesn't exist
 */
function loadYaml($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    
    $content = file_get_contents($filename);
    
    // Use PHP YAML extension
    if (!function_exists('yaml_parse')) {
        // Provide clear error message if yaml extension is missing
        error_log('ERROR: PHP YAML extension is required but not installed. Please install via: apt-get install php-yaml or pecl install yaml');
        // Return empty array to allow application to run (will just have no data)
        return [];
    }
    
    $result = yaml_parse($content);
    
    // Handle parsing errors
    if ($result === false) {
        error_log("ERROR: Failed to parse YAML file: $filename");
        return [];
    }
    
    return $result;
}

/**
 * Save data to YAML file
 * 
 * This function serves as the primary data persistence interface.
 * To migrate to database storage, replace this function with
 * database operations while maintaining the same parameter structure.
 * 
 * NOTE: This function requires the PHP YAML extension. If not available in
 * the environment, install it via: apt-get install php-yaml or pecl install yaml
 * 
 * @param string $filename Path to YAML file
 * @param array $data Data structure to persist
 * @return int|false Number of bytes written or false on failure
 */
function saveYaml($filename, $data) {
    if (!function_exists('yaml_emit')) {
        // Provide clear error message if yaml extension is missing
        error_log('ERROR: PHP YAML extension is required but not installed. Please install via: apt-get install php-yaml or pecl install yaml');
        return false;
    }
    
    $yaml = yaml_emit($data);
    
    if ($yaml === false) {
        error_log("ERROR: Failed to emit YAML for file: $filename");
        return false;
    }
    
    return file_put_contents($filename, $yaml);
}

/**
 * Parse identifier using regex to extract name
 * 
 * Extracts the first captured group from the identifier using regex pattern.
 * Returns error if no match is found.
 * 
 * Example: "item-001-coffee-machine" -> "coffee-machine" (with default regex)
 * 
 * @param string $identifier Raw item identifier
 * @param array $config Configuration array with identifier parsing rules
 * @return string Extracted name from identifier
 */
function parseIdentifier($identifier, $config) {
    $regex = $config['identifier']['regex'] ?? '^[a-z]+-\\d+-(.*?)$';
    
    // Extract first captured group using regex pattern
    if (preg_match('/' . $regex . '/i', $identifier, $matches)) {
        if (isset($matches[1]) && $matches[1] !== '') {
            return $matches[1];
        }
    }
    
    // Error: no match found
    error_log("ERROR: Identifier '$identifier' does not match regex pattern '$regex'");
    return 'ERROR: Invalid identifier format';
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
