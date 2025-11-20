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

function loadYaml($filename) {
    if (!file_exists($filename)) return [];
    if (!function_exists('yaml_parse_file')) {
        error_log('ERROR: PHP YAML extension is required but not installed. Please install via: apt-get install php-yaml or pecl install yaml');
        return [];
    }
    $result = @yaml_parse_file($filename);
    if ($result === false || $result === null) {
        error_log("ERROR: Failed to parse YAML file: $filename");
        return [];
    }
    return $result;
}

function saveYaml($filename, $data) {
    if (!function_exists('yaml_emit')) {
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

function parseIdentifier($identifier, $config) {
    $regex = $config['identifier']['regex'];
    $groups = $config['identifier']['groups'];
    
    if (preg_match('/' . $regex . '/i', $identifier, $matches)) {
        $final_name_parts = [];
        foreach ($groups as $group_index) {
            if (isset($matches[$group_index])) {
                $final_name_parts[] = $matches[$group_index];
            }  
        }
        $raw = implode(' ', $final_name_parts);
        // Normalize delimiters (dashes, underscores, slashes) into spaces
        $normalized = preg_replace('/[-_\/]+/', ' ', $raw);
        // Collapse multiple spaces and trim
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));
        // Title-case for nicer display
        $normalized = ucwords(mb_strtolower($normalized));

        return $normalized;
    }
    
    error_log("ERROR: Identifier '$identifier' does not match regex pattern '$regex'");
    // If no regex match, try to normalize the raw identifier itself
    $fallback = preg_replace('/[-_\/]+/', ' ', $identifier);
    $fallback = preg_replace('/\s+/', ' ', trim($fallback));
    return ucwords(mb_strtolower($fallback));
}

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
