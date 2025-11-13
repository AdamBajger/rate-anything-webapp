# rate-anything-webapp

This project contains a lightweight web app to streamline simple ratings of anything. It is based on QR codes that identify things, with support for multiple UUID-based rating configurations.

## Overview

The application uses UUIDs to identify different rating configurations. Each UUID corresponds to a specific rating setup (e.g., restaurant rating, product review, service quality) defined in the `config.yaml` file. Users can scan QR codes to identify items and rate them according to the selected configuration.

## Features

- 📱 **QR Code Scanner**: Built-in camera-based QR code scanning using html5-qrcode library
- 🎯 **UUID-based Configurations**: Multiple rating setups, each with its own UUID
- ⭐ **Flexible Rating Scales**: Configurable min/max rating values per configuration
- 📊 **Ratings Storage**: All ratings stored in YAML format with averages and counts
- 🎨 **Clean, Responsive UI**: Modern gradient design that works on all devices
- ⚙️ **Easy Configuration**: Simple YAML configuration file
- 🔒 **Security**: Input validation, XSS protection, and path sanitization

## Requirements

- PHP 7.0 or higher
- PHP YAML extension (`php-yaml`)
- Web server (Apache, Nginx, or PHP built-in server)
- Modern web browser with camera support for QR code scanning

## Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/AdamBajger/rate-anything-webapp.git
   cd rate-anything-webapp
   ```

2. Install PHP YAML extension (if not already installed):
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-yaml
   
   # macOS with Homebrew
   pecl install yaml
   
   # Or via PECL
   sudo pecl install yaml
   ```

3. Start the PHP development server:
   ```bash
   php -S localhost:8080
   ```

4. Open your browser and navigate to `http://localhost:8080`

## Usage

### Selecting a Configuration

1. Navigate to `http://localhost:8080/index.php` (without UUID parameter)
2. You'll see a list of all available rating configurations
3. Click "Start Rating" on any configuration to begin

### Direct Access with UUID

Access a specific configuration directly by providing its UUID:

```
http://localhost:8080/index.php?uuid=550e8400-e29b-41d4-a716-446655440000
```

### Rating Items

1. **Start Scanning**: Click "Start Scanning" to activate your device's camera
2. **Scan QR Code**: Point your camera at a QR code containing an item identifier
3. **Select Rating**: Choose a rating based on the configuration's scale
4. **Submit**: Click "Submit Rating" to save your rating
5. **Continue**: Scan another item or change configuration

### Example URLs

- Restaurant Rating: `index.php?uuid=550e8400-e29b-41d4-a716-446655440000`
- Product Review: `index.php?uuid=6ba7b810-9dad-11d1-80b4-00c04fd430c8`
- Service Quality: `index.php?uuid=7c9e6679-7425-40de-944b-e07fc1f90ae7`

## Configuration

Rating configurations are stored in `config.yaml`. Each configuration is identified by a UUID and includes:

- `name` - Display name of the rating configuration
- `description` - Description of what is being rated
- `type` - Type of rating (e.g., restaurant, product, service)
- `rating_scale` - Min and max values for the rating scale
- `categories` - Array of rating categories with weights (displayed to users)

### Configuration File Structure

```yaml
# Storage configuration
storage:
  ratings_file: ratings.yaml

# UUID-based configurations
configs:
  550e8400-e29b-41d4-a716-446655440000:
    name: "Restaurant Quality Rating"
    description: "Rate the quality of your dining experience"
    type: "restaurant"
    rating_scale:
      min: 1
      max: 5
    categories:
      - name: "Food Quality"
        weight: 0.4
      - name: "Service"
        weight: 0.3
      - name: "Ambiance"
        weight: 0.2
      - name: "Value for Money"
        weight: 0.1
```

### Adding New Configurations

1. Generate a new UUID (v4): https://www.uuidgenerator.net/
2. Add the configuration to `config.yaml` under `configs:`
3. Set the name, description, type, rating scale, and categories
4. Save the file - the new configuration will be immediately available

## File Structure

```
rate-anything-webapp/
├── config.yaml      # Application and rating configurations
├── index.php        # Main application page with UUID-based QR scanner
├── rate.php         # Backend handler for rating submissions
├── ratings.yaml     # Storage for all ratings (auto-generated)
├── .gitignore       # Git ignore rules
└── README.md        # This file
```

## Data Format

Ratings are stored in `ratings.yaml` with UUID-prefixed keys:

```yaml
550e8400-e29b-41d4-a716-446655440000::item_123:
  uuid: 550e8400-e29b-41d4-a716-446655440000
  item_id: item_123
  config_name: "Restaurant Quality Rating"
  ratings:
    - rating: 5
      timestamp: "2025-11-13 10:30:00"
    - rating: 4
      timestamp: "2025-11-13 10:35:00"
  count: 2
  sum: 9
  average: 4.5
```

This format allows the same item to be rated under different configurations without conflicts.

## QR Code Integration

Generate QR codes that encode item identifiers. When users scan these codes with the app:
1. The QR code content (item ID) is captured
2. The item ID is displayed along with rating options
3. Users select a rating and submit
4. The rating is saved with the UUID, item ID, and timestamp

You can use any QR code generator to create codes for your items. The codes should contain simple text identifiers (e.g., "ITEM001", "TABLE-5", "PRODUCT-ABC").

## Security Notes

- UUID format validation prevents malformed requests
- Configuration file path validation prevents directory traversal
- Item IDs are length-limited and sanitized
- All user input is escaped with `htmlspecialchars()` to prevent XSS
- The `ratings.yaml` file is excluded from version control (via `.gitignore`)

## License

This project is open source and available under the MIT License.

