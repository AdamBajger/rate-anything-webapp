# rate-anything-webapp

This project contains a lightweight web app to streamline simple ratings of anything. It is based on QR codes that identify things.

## Features

- 📱 QR Code scanning using html5-qrcode library
- ⭐ Configurable rating scale (default: 1-5)
- 📊 Ratings stored in YAML format
- 🎨 Clean, responsive UI
- ⚙️ Easy configuration via YAML

## Requirements

- PHP 7.0 or higher
- PHP YAML extension (`php-yaml`)
- Web server (Apache, Nginx, or PHP built-in server)

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
   php -S localhost:8000
   ```

4. Open your browser and navigate to `http://localhost:8000`

## Configuration

Edit `config.yaml` to customize the application:

```yaml
# Rating scale configuration
rating_scale:
  min: 1      # Minimum rating value
  max: 5      # Maximum rating value
  step: 1     # Step between ratings

# Storage configuration
storage:
  ratings_file: ratings.yaml  # File to store ratings

# Application settings
app:
  title: "Rate Anything"
  description: "Scan QR code to rate"
```

## Usage

1. **Start Scanning**: Click the "Start Scanning" button to activate your device's camera
2. **Scan QR Code**: Point your camera at a QR code containing an item identifier
3. **Rate Item**: Select a rating from the available options
4. **Submit**: Click "Submit Rating" to save your rating
5. **Repeat**: Scan another item or close the app

## File Structure

```
rate-anything-webapp/
├── config.yaml      # Application configuration
├── index.php        # Main application page with QR scanner
├── rate.php         # Backend handler for rating submissions
├── ratings.yaml     # Storage for all ratings (auto-generated)
├── .gitignore       # Git ignore rules
└── README.md        # This file
```

## Data Format

Ratings are stored in `ratings.yaml` with the following structure:

```yaml
item_id_1:
  ratings:
    - rating: 5
      timestamp: "2025-11-12 10:30:00"
    - rating: 4
      timestamp: "2025-11-12 10:35:00"
  count: 2
  sum: 9
  average: 4.5
```

## Security Notes

- The `ratings.yaml` file is excluded from version control (via `.gitignore`)
- Input validation is performed on all rating submissions
- XSS protection via `htmlspecialchars()` in PHP

## License

This project is open source and available under the MIT License.
