# rate-anything-webapp

This project contains a lightweight web app to streamline simple ratings of anything. It is based on QR codes that identify things.

## Features

- **QR Code Scanner**: Scan QR codes to quickly identify items
- **Manual Entry**: Select from tracked items or enter identifiers manually
- **Configurable Rating Scale**: Define your own rating scale (1-5 by default)
- **Leaderboard**: View rankings and statistics for all rated items
- **Data Persistence**: All ratings are stored in YAML format
- **Human-Readable Names**: Automatically parse identifiers into readable names using regex

## Installation

1. Clone this repository to your web server
2. Ensure PHP 7.0+ is installed
3. Configure your web server to serve the directory (Apache, Nginx, or PHP built-in server)
4. Optionally install the PECL YAML extension for better performance:
   ```bash
   pecl install yaml
   ```
   (The app includes a fallback YAML parser if the extension is not available)

## Usage

### Quick Start with PHP Built-in Server

```bash
php -S localhost:8000
```

Then open http://localhost:8000 in your browser.

### Configuration

Edit `config.yaml` to customize:

- **Rating Scale**: Set min/max values and labels
- **Identifier Parsing**: Define regex pattern to extract human-readable names from identifiers

Example identifier format: `item-001-coffee-machine` → `Coffee Machine`

### Workflow

1. **Rate an Item** (index.php):
   - Scan a QR code containing an identifier
   - OR select from already tracked items
   - OR manually enter an identifier
   - Choose a rating from the scale
   - Submit

2. **View Results** (leaderboard.php):
   - See rankings sorted by average rating
   - View detailed statistics and rating distributions
   - Check recent ratings for each item

3. **Data Storage**:
   - All data is stored in `data.yaml`
   - Each rating includes a timestamp
   - Items are automatically created when first rated

## File Structure

- `index.php` - Main rating form with QR scanner
- `submit.php` - Handles form submission and data storage
- `leaderboard.php` - Displays statistics and rankings
- `functions.php` - Helper functions for YAML parsing and identifier formatting
- `style.css` - Styling for the interface
- `config.yaml` - Application configuration
- `data.yaml` - Rating data storage

## Requirements

- PHP 7.0 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- Modern web browser with camera access (for QR scanning)

## License

Open source - feel free to use and modify as needed.
