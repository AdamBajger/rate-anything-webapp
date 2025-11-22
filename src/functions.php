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
    error_log("Config is: " . print_r($config, true));
    $regex = null;
    if (isset($config['identifier']['regex'])) {
        $regex = $config['identifier']['regex'];
    }
    $groups = [];
    if (isset($config['identifier']['groups'])) {
        $groups = $config['identifier']['groups'];
        if (!is_array($groups)) {
            $groups = [$groups];
        }
    }
    error_log("Groups: " . print_r($groups, true));
    if ($regex === null || empty($groups)) {
        return $identifier;
    }
    $select_matches = [];
    if (preg_match($regex, $identifier, $matches, PREG_UNMATCHED_AS_NULL)) {
        $parts = [];
        foreach ($groups as $g) {
            if (isset($matches[$g]) && $matches[$g] !== null) {
                $parts[] = $matches[$g];
                error_log("DEBUG: Matched group $g: " . $matches[$g]);
            }
        }
        if (!empty($parts)) {
            // Combine parts into a display name
            $name = implode(' ', $parts);
            // Convert to title case
            $name = ucwords(strtolower(str_replace(['-', '_'], ' ', $name)));
            return $name;
        }
        // warn if no groups matched
        error_log("WARNING: No matching groups found for identifier: $identifier");
        return $identifier;
    }   
    // warn if regex did not match
    error_log("WARNING: Regex did not match identifier: $identifier");
    return $identifier;
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
