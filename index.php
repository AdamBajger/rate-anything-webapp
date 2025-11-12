<?php
/**
 * Rate Anything Webapp - Main Entry Point
 * 
 * This file serves as the entrypoint for the rating application.
 * It loads a UUID from the URL-encoded GET request and uses it to
 * load a specific rating setup from the config.yaml file.
 */

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('CONFIG_FILE', __DIR__ . '/config.yaml');

/**
 * Load and parse the YAML configuration file
 * 
 * @return array|null The parsed configuration or null on error
 */
function loadConfig() {
    if (!file_exists(CONFIG_FILE)) {
        return null;
    }
    
    // Try using the YAML extension if available
    if (function_exists('yaml_parse_file')) {
        return yaml_parse_file(CONFIG_FILE);
    }
    
    // Fallback: basic YAML parsing for simple structures
    // Note: For production use, consider using Symfony YAML component
    $content = file_get_contents(CONFIG_FILE);
    
    // This is a very basic YAML parser - works for our simple structure
    // For complex YAML, use a proper library
    $config = ['configs' => []];
    $lines = explode("\n", $content);
    $currentUuid = null;
    $currentConfig = [];
    $currentCategories = [];
    $inCategories = false;
    $indent = 0;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Skip comments and empty lines
        if (empty($trimmed) || $trimmed[0] === '#') {
            continue;
        }
        
        // Detect UUID lines (they start with a UUID pattern at indent level 2)
        if (preg_match('/^  ([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}):/i', $line, $matches)) {
            // Save previous config if exists
            if ($currentUuid !== null) {
                if (!empty($currentCategories)) {
                    $currentConfig['categories'] = $currentCategories;
                }
                $config['configs'][$currentUuid] = $currentConfig;
            }
            
            // Start new config
            $currentUuid = $matches[1];
            $currentConfig = [];
            $currentCategories = [];
            $inCategories = false;
            continue;
        }
        
        // Parse configuration fields
        if ($currentUuid !== null) {
            if (preg_match('/^    categories:/', $line)) {
                $inCategories = true;
                continue;
            }
            
            if ($inCategories) {
                if (preg_match('/^      - name: "(.+)"/', $line, $matches)) {
                    $currentCategories[] = ['name' => $matches[1]];
                } elseif (preg_match('/^        weight: (.+)/', $line, $matches)) {
                    $lastIndex = count($currentCategories) - 1;
                    if ($lastIndex >= 0) {
                        $currentCategories[$lastIndex]['weight'] = floatval($matches[1]);
                    }
                }
            } else {
                if (preg_match('/^    (\w+): "(.+)"/', $line, $matches)) {
                    $currentConfig[$matches[1]] = $matches[2];
                } elseif (preg_match('/^    (\w+): (.+)/', $line, $matches)) {
                    $currentConfig[$matches[1]] = $matches[2];
                } elseif (preg_match('/^      (\w+): (.+)/', $line, $matches)) {
                    // Handle nested properties like rating_scale
                    if (!isset($currentConfig['rating_scale'])) {
                        $currentConfig['rating_scale'] = [];
                    }
                    $currentConfig['rating_scale'][$matches[1]] = intval($matches[2]);
                }
            }
        }
    }
    
    // Save last config
    if ($currentUuid !== null) {
        if (!empty($currentCategories)) {
            $currentConfig['categories'] = $currentCategories;
        }
        $config['configs'][$currentUuid] = $currentConfig;
    }
    
    return $config;
}

/**
 * Validate UUID format (RFC 4122)
 * 
 * @param string $uuid The UUID to validate
 * @return bool True if valid, false otherwise
 */
function isValidUuid($uuid) {
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    return preg_match($pattern, $uuid) === 1;
}

/**
 * Get rating configuration by UUID
 * 
 * @param string $uuid The UUID to look up
 * @param array $config The full configuration array
 * @return array|null The rating configuration or null if not found
 */
function getConfigByUuid($uuid, $config) {
    if (!isset($config['configs'][$uuid])) {
        return null;
    }
    
    return $config['configs'][$uuid];
}

/**
 * Display error message
 * 
 * @param string $message The error message to display
 */
function displayError($message) {
    http_response_code(400);
    echo "<!DOCTYPE html>\n";
    echo "<html>\n<head>\n";
    echo "<title>Error - Rate Anything</title>\n";
    echo "<style>body { font-family: Arial, sans-serif; margin: 40px; } .error { color: #d32f2f; background: #ffebee; padding: 20px; border-radius: 4px; }</style>\n";
    echo "</head>\n<body>\n";
    echo "<h1>Error</h1>\n";
    echo "<div class='error'>" . htmlspecialchars($message) . "</div>\n";
    echo "<p><a href='?'>Try again</a></p>\n";
    echo "</body>\n</html>";
    exit;
}

/**
 * Display rating configuration
 * 
 * @param array $ratingConfig The rating configuration to display
 * @param string $uuid The UUID of the configuration
 */
function displayRatingConfig($ratingConfig, $uuid) {
    echo "<!DOCTYPE html>\n";
    echo "<html>\n<head>\n";
    echo "<title>Rate Anything - " . htmlspecialchars($ratingConfig['name'] ?? 'Rating') . "</title>\n";
    echo "<style>\n";
    echo "body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }\n";
    echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
    echo "h1 { color: #1976d2; margin-top: 0; }\n";
    echo ".description { color: #666; margin-bottom: 20px; }\n";
    echo ".info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin-bottom: 20px; }\n";
    echo ".info-item { margin: 5px 0; }\n";
    echo ".info-label { font-weight: bold; color: #1565c0; }\n";
    echo ".categories { margin-top: 20px; }\n";
    echo ".category { background: #f5f5f5; padding: 10px 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #1976d2; }\n";
    echo ".category-name { font-weight: bold; }\n";
    echo ".category-weight { color: #666; font-size: 0.9em; }\n";
    echo ".uuid { font-family: monospace; background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; }\n";
    echo "</style>\n";
    echo "</head>\n<body>\n";
    echo "<div class='container'>\n";
    
    // Display configuration details
    echo "<h1>" . htmlspecialchars($ratingConfig['name'] ?? 'Rating Configuration') . "</h1>\n";
    
    if (isset($ratingConfig['description'])) {
        echo "<p class='description'>" . htmlspecialchars($ratingConfig['description']) . "</p>\n";
    }
    
    echo "<div class='info'>\n";
    echo "<div class='info-item'><span class='info-label'>UUID:</span> <span class='uuid'>" . htmlspecialchars($uuid) . "</span></div>\n";
    
    if (isset($ratingConfig['type'])) {
        echo "<div class='info-item'><span class='info-label'>Type:</span> " . htmlspecialchars($ratingConfig['type']) . "</div>\n";
    }
    
    if (isset($ratingConfig['rating_scale'])) {
        $scale = $ratingConfig['rating_scale'];
        $min = $scale['min'] ?? 1;
        $max = $scale['max'] ?? 5;
        echo "<div class='info-item'><span class='info-label'>Rating Scale:</span> " . $min . " to " . $max . "</div>\n";
    }
    echo "</div>\n";
    
    // Display categories
    if (isset($ratingConfig['categories']) && is_array($ratingConfig['categories'])) {
        echo "<div class='categories'>\n";
        echo "<h2>Rating Categories</h2>\n";
        
        foreach ($ratingConfig['categories'] as $category) {
            echo "<div class='category'>\n";
            echo "<div class='category-name'>" . htmlspecialchars($category['name']) . "</div>\n";
            
            if (isset($category['weight'])) {
                $percentage = ($category['weight'] * 100);
                echo "<div class='category-weight'>Weight: " . $percentage . "%</div>\n";
            }
            
            echo "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    echo "</div>\n";
    echo "</body>\n</html>";
}

/**
 * Display help/instructions page
 */
function displayHelp() {
    echo "<!DOCTYPE html>\n";
    echo "<html>\n<head>\n";
    echo "<title>Rate Anything - Help</title>\n";
    echo "<style>\n";
    echo "body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }\n";
    echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
    echo "h1 { color: #1976d2; margin-top: 0; }\n";
    echo "code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }\n";
    echo ".example { background: #e8f5e9; padding: 15px; border-radius: 4px; margin: 15px 0; }\n";
    echo "a { color: #1976d2; }\n";
    echo "</style>\n";
    echo "</head>\n<body>\n";
    echo "<div class='container'>\n";
    echo "<h1>Welcome to Rate Anything</h1>\n";
    echo "<p>This application allows you to rate different things based on QR codes.</p>\n";
    echo "<h2>How to Use</h2>\n";
    echo "<p>To load a rating configuration, provide a UUID in the URL:</p>\n";
    echo "<div class='example'>\n";
    echo "<code>index.php?uuid=550e8400-e29b-41d4-a716-446655440000</code>\n";
    echo "</div>\n";
    echo "<h2>Available Configurations</h2>\n";
    echo "<ul>\n";
    echo "<li><a href='?uuid=550e8400-e29b-41d4-a716-446655440000'>Restaurant Quality Rating</a></li>\n";
    echo "<li><a href='?uuid=6ba7b810-9dad-11d1-80b4-00c04fd430c8'>Product Review</a></li>\n";
    echo "<li><a href='?uuid=7c9e6679-7425-40de-944b-e07fc1f90ae7'>Service Quality</a></li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    echo "</body>\n</html>";
}

// Main application logic
try {
    // Load configuration
    $config = loadConfig();
    
    if ($config === null) {
        displayError("Configuration file not found or could not be parsed.");
    }
    
    // Get UUID from GET request
    $uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : null;
    
    // If no UUID provided, show help page
    if ($uuid === null || $uuid === '') {
        displayHelp();
        exit;
    }
    
    // Validate UUID format
    if (!isValidUuid($uuid)) {
        displayError("Invalid UUID format. Please provide a valid UUID (e.g., 550e8400-e29b-41d4-a716-446655440000).");
    }
    
    // Get rating configuration for the UUID
    $ratingConfig = getConfigByUuid($uuid, $config);
    
    if ($ratingConfig === null) {
        displayError("No rating configuration found for UUID: " . htmlspecialchars($uuid));
    }
    
    // Display the rating configuration
    displayRatingConfig($ratingConfig, $uuid);
    
} catch (Exception $e) {
    displayError("An error occurred: " . $e->getMessage());
}
