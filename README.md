# Rate Anything Web Application

A lightweight web application for rating items using QR code scanning. The application provides a simple, responsive interface for collecting and displaying ratings with persistent storage in YAML format.

## Overview

This application enables users to scan QR codes or manually select items to rate them on a configurable scale (default 1-5). All ratings are stored persistently in YAML files and displayed in a leaderboard with detailed statistics. The design is modular and follows best practices for potential future migration to database storage.

## Architecture

The application consists of:
- **index.php**: Main rating interface with QR code scanner and rating form
- **submit.php**: Backend handler for processing and storing ratings
- **leaderboard.php**: Display interface for ratings statistics and rankings
- **functions.php**: Core utility functions for data persistence and processing
- **style.css**: Responsive stylesheet with gradient design
- **config.yaml**: Application configuration (rating scale, identifier parsing rules)
- **data.yaml**: Persistent storage for all ratings (auto-generated)

## Key Features

- QR Code Scanner: Camera-based scanning using html5-qrcode library
- Flexible Rating Scale: Configurable minimum and maximum values with custom labels
- Persistent Storage: YAML-based data storage with complete rating history
- Statistics Dashboard: Leaderboard with averages, distributions, and recent ratings
- Responsive Design: Mobile-friendly interface that works on all devices
- Identifier Parsing: Configurable rules for converting identifiers to human-readable names
- Input Validation: XSS protection and sanitization for all user inputs
- Modular Design: Storage layer designed for easy migration to database systems

## System Requirements

- PHP 8.0 or higher
- PHP YAML extension (php-yaml)
- Web server (Apache recommended for production)
- Modern web browser with camera support for QR scanning
- Docker and Docker Compose (for containerized deployment)

## Docker Deployment (Recommended)

The application is designed to run in a Docker container for consistent deployment across environments.

### Important: PHP YAML Extension Requirement

This application requires the PHP YAML extension. Due to connectivity limitations in certain build environments, the YAML extension cannot always be automatically installed during the Docker build process. 

**For production deployment on Google Compute Engine or similar platforms with full internet access, the extension should install successfully during the build.**

If you encounter issues, the application will display an error message requesting installation of the extension. The code is designed to handle missing extensions gracefully without crashing.

### Quick Start with Docker

1. Clone the repository:
   ```bash
   git clone https://github.com/AdamBajger/rate-anything-webapp.git
   cd rate-anything-webapp
   ```

2. Build the Docker image:
   ```bash
   docker build -t rate-anything-webapp .
   ```

3. Run the container:
   ```bash
   docker run -d -p 8080:80 --name rate-app rate-anything-webapp
   ```

4. Access the application at `http://localhost:8080`

### Persistent Data Storage

To persist ratings across container restarts, mount a volume for the data file:

```bash
docker run -d -p 8080:80 \
  -v $(pwd)/data.yaml:/var/www/html/data.yaml \
  --name rate-app rate-anything-webapp
```

### Custom Configuration

To use custom rating scales and identifier parsing rules:

```bash
docker run -d -p 8080:80 \
  -v $(pwd)/config.yaml:/var/www/html/config.yaml \
  -v $(pwd)/data.yaml:/var/www/html/data.yaml \
  --name rate-app rate-anything-webapp
```

### Docker Run Options

Basic deployment:
```bash
docker run -d -p 8080:80 --name rate-app rate-anything-webapp
```

With persistent data volume:
```bash
docker run -d -p 8080:80 \
  -v $(pwd)/data.yaml:/var/www/html/data.yaml \
  --name rate-app rate-anything-webapp
```

With custom configuration:
```bash
docker run -d -p 8080:80 \
  -v $(pwd)/config.yaml:/var/www/html/config.yaml \
  -v $(pwd)/data.yaml:/var/www/html/data.yaml \
  --name rate-app rate-anything-webapp
```

## Manual Installation (Development Only)

For local development without Docker:

1. Install PHP 8.0+ and the YAML extension:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php php-yaml
   
   # macOS with Homebrew
   brew install php
   pecl install yaml
   ```

2. Start the PHP development server:
   ```bash
   php -S localhost:8080
   ```

3. Open your browser and navigate to `http://localhost:8080`

## Usage

### Rating Items

1. **Scan QR Code**: The scanner starts automatically on page load. Point your camera at a QR code containing an item identifier
2. **Manual Entry**: Alternatively, select from tracked items or enter an identifier manually
3. **Select Rating**: Choose a rating from the configured scale (default: 1-5)
4. **Submit**: Click "Submit Rating" to save your rating
5. **View Results**: You'll be redirected to the leaderboard showing all ratings

### Identifier Format

Item identifiers should follow the pattern configured in `config.yaml`. Default pattern:
- Format: `item-XXX-descriptive-name`
- Example: `item-001-coffee-machine`
- The application extracts the descriptive part using regex (e.g., "coffee-machine")

## Configuration

The application is configured through `config.yaml` which controls rating scale and identifier parsing.

### Configuration File Structure

```yaml
# Rating scale configuration
rating:
  min: 1
  max: 5
  labels:
    1: "Poor"
    2: "Fair"
    3: "Good"
    4: "Very Good"
    5: "Excellent"

# Identifier parsing configuration
identifier:
  # Regular expression to extract name (first captured group)
  regex: "^[a-z]+-\\d+-(.*?)$"
```

### Customizing Rating Scale

Edit the `rating` section in `config.yaml`:
- `min`: Minimum rating value (default: 1)
- `max`: Maximum rating value (default: 5)
- `labels`: Custom labels for each rating value

### Customizing Identifier Parsing

Edit the `identifier` section to control how item identifiers are parsed:
- `regex`: Pattern to extract the name from identifiers (first captured group is used)

Example: With regex `^[a-z]+-\\d+-(.*?)$`, the identifier "item-001-coffee-machine" extracts "coffee-machine"

## Data Storage

### Storage Format

Ratings are stored in `data.yaml` with the following structure:

```yaml
items:
  item-001-coffee-machine:
    name: Coffee Machine
    ratings:
      - rating: 5
        timestamp: "2025-11-15 10:21:42"
      - rating: 4
        timestamp: "2025-11-15 12:30:15"
```

### Storage Layer Design

The storage layer in `functions.php` is designed to be modular and easily replaceable:

- `loadYaml($filename)`: Loads data from YAML file
- `saveYaml($filename, $data)`: Saves data to YAML file
- `calculateStats($ratings)`: Computes statistics from rating array

To migrate to a database:
1. Replace `loadYaml()` and `saveYaml()` with database operations
2. Maintain the same data structure (array format)
3. Update `calculateStats()` if needed for SQL aggregation
4. The rest of the application code remains unchanged

### Data Persistence

- `data.yaml` is automatically created on first rating submission
- The file is excluded from version control via `.gitignore`
- In Docker deployments, use volumes to persist data between container restarts
- Backup `data.yaml` regularly to prevent data loss

## QR Code Integration

Generate QR codes that encode item identifiers. Recommended tools:
- Online: qr-code-generator.com, qrcode-monkey.com
- Command line: `qrencode` utility
- Programmatic: Any QR code generation library

### QR Code Content Format

The QR code should contain a simple text identifier:
- Good: `item-001-coffee-machine`
- Good: `device-abc-123`
- Good: `product-XYZ`

The application will scan the QR code and use the content as the item identifier.

## Security

The application implements several security measures:

- **Input Sanitization**: All user inputs are sanitized before display
- **XSS Protection**: `htmlspecialchars()` used for all output
- **Path Validation**: File operations restricted to application directory
- **Data Validation**: Rating values validated against configured scale
- **No SQL Injection**: File-based storage eliminates SQL injection risks
- **CORS Headers**: Properly configured for cross-origin requests

## File Structure

```
rate-anything-webapp/
├── index.php           # Main rating interface with QR scanner
├── submit.php          # Rating submission handler
├── leaderboard.php     # Statistics and leaderboard display
├── functions.php       # Core utility functions and storage layer
├── style.css           # Responsive stylesheet
├── config.yaml         # Application configuration
├── data.yaml           # Persistent rating storage (auto-generated)
├── Dockerfile          # Docker container definition
├── .dockerignore       # Docker build exclusions
├── .gitignore          # Git exclusions
└── README.md           # This documentation
```

## Troubleshooting

### Camera Not Working
- Ensure HTTPS is enabled (browsers require secure context for camera access)
- Check browser permissions for camera access
- Try using manual entry instead

### Ratings Not Saving
- Check file permissions on `data.yaml`
- Ensure PHP has write access to the application directory
- In Docker, verify volume mounts are configured correctly

### Docker Container Issues
- Check container logs: `docker logs rate-app`
- Verify port 8080 is not already in use
- Ensure Docker has sufficient resources

## License

This project is open source and available under the MIT License.
