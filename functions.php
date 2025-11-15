<?php
/**
 * Helper functions for the rate-anything-webapp
 */

/**
 * Simple YAML parser for basic structures
 * Supports simple key-value pairs and nested structures
 */
function loadYaml($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    
    $content = file_get_contents($filename);
    
    // Use symfony/yaml if available, otherwise fall back to simple parser
    if (function_exists('yaml_parse')) {
        return yaml_parse($content);
    }
    
    // Simple YAML parser for basic structures
    return parseSimpleYaml($content);
}

/**
 * Save data to YAML file
 */
function saveYaml($filename, $data) {
    if (function_exists('yaml_emit')) {
        $yaml = yaml_emit($data);
    } else {
        $yaml = simpleYamlEmit($data);
    }
    
    return file_put_contents($filename, $yaml);
}

/**
 * Simple YAML parser (fallback)
 */
function parseSimpleYaml($content) {
    $lines = explode("\n", $content);
    $result = [];
    $current = &$result;
    $stack = [];
    $lastIndent = 0;
    
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (preg_match('/^\s*#/', $line) || trim($line) === '') {
            continue;
        }
        
        // Calculate indentation
        preg_match('/^(\s*)/', $line, $matches);
        $indent = strlen($matches[1]);
        $line = trim($line);
        
        // Handle indent changes
        if ($indent < $lastIndent) {
            $levelsBack = ($lastIndent - $indent) / 2;
            for ($i = 0; $i < $levelsBack; $i++) {
                array_pop($stack);
            }
            $current = &$stack[count($stack) - 1];
        }
        
        $lastIndent = $indent;
        
        // Parse key-value pairs
        if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
            $key = trim($matches[1]);
            $value = trim($matches[2]);
            
            if ($value === '' || $value === '{}') {
                // Empty object/array
                $current[$key] = [];
                $stack[] = &$current[$key];
                $current = &$current[$key];
            } else {
                // Parse value
                if ($value === 'true') {
                    $current[$key] = true;
                } elseif ($value === 'false') {
                    $current[$key] = false;
                } elseif (is_numeric($value)) {
                    $current[$key] = strpos($value, '.') !== false ? (float)$value : (int)$value;
                } elseif (preg_match('/^["\'](.+)["\']$/', $value, $valueMatches)) {
                    $current[$key] = $valueMatches[1];
                } else {
                    $current[$key] = $value;
                }
            }
        } elseif (preg_match('/^-\s+(.+)$/', $line, $matches)) {
            // Array item
            $value = trim($matches[1]);
            if (preg_match('/^([^:]+):\s*(.*)$/', $value, $kvMatches)) {
                $item = [];
                $key = trim($kvMatches[1]);
                $val = trim($kvMatches[2]);
                if (is_numeric($val)) {
                    $item[$key] = strpos($val, '.') !== false ? (float)$val : (int)$val;
                } else {
                    $item[$key] = $val;
                }
                $current[] = $item;
            } else {
                $current[] = $value;
            }
        }
    }
    
    return $result;
}

/**
 * Simple YAML emitter
 */
function simpleYamlEmit($data, $indent = 0) {
    $yaml = '';
    $spaces = str_repeat(' ', $indent);
    
    if (is_array($data)) {
        // Check if it's an associative array or indexed array
        $isAssoc = array_keys($data) !== range(0, count($data) - 1);
        
        if ($isAssoc) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    if (empty($value)) {
                        $yaml .= $spaces . $key . ": {}\n";
                    } else {
                        $yaml .= $spaces . $key . ":\n";
                        $yaml .= simpleYamlEmit($value, $indent + 2);
                    }
                } else {
                    $yaml .= $spaces . $key . ": " . formatYamlValue($value) . "\n";
                }
            }
        } else {
            foreach ($data as $value) {
                if (is_array($value)) {
                    $yaml .= $spaces . "-\n";
                    $yaml .= simpleYamlEmit($value, $indent + 2);
                } else {
                    $yaml .= $spaces . "- " . formatYamlValue($value) . "\n";
                }
            }
        }
    }
    
    return $yaml;
}

/**
 * Format value for YAML output
 */
function formatYamlValue($value) {
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    } elseif (is_numeric($value)) {
        return $value;
    } elseif (is_string($value) && (strpos($value, ':') !== false || strpos($value, '#') !== false)) {
        return '"' . str_replace('"', '\\"', $value) . '"';
    } else {
        return $value;
    }
}

/**
 * Parse identifier to human-readable format using config regex
 */
function parseIdentifier($identifier, $config) {
    $regex = $config['identifier']['regex'] ?? '^[a-z]+-\\d+-(.*?)$';
    $format = $config['identifier']['format'] ?? 'title_case';
    $separator = $config['identifier']['separator'] ?? ' ';
    
    // Try to extract the meaningful part using regex
    if (preg_match('/' . $regex . '/i', $identifier, $matches)) {
        $name = $matches[1] ?? $identifier;
    } else {
        $name = $identifier;
    }
    
    // Replace dashes and underscores with separator
    $name = str_replace(['-', '_'], $separator, $name);
    
    // Apply formatting
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
            // Keep as is
            break;
    }
    
    return $name;
}

/**
 * Calculate statistics for an item
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
