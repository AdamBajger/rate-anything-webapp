# Rate Anything Web Application

A lightweight PHP web application for rating items using QR code scanning. Designed for easy deployment with Docker and supports multiple isolated instances.

## Features

- **QR Code Scanner**: Camera-based scanning using html5-qrcode library
- **Flexible Rating Scale**: Configurable rating values with custom labels
- **Multi-Instance Support**: Run multiple isolated rating instances from a single deployment
- **Localization**: Built-in support for multiple languages (English, Czech)
- **Data Backup**: Download ratings as YAML files for backup
- **Responsive Design**: Mobile-friendly interface
- **Persistent Storage**: YAML-based data storage

## Project Structure

```
rate-anything-webapp/
├── public/              # Webroot (served by nginx)
│   ├── index.php        # Main rating interface with QR scanner
│   ├── leaderboard.php  # Rankings and statistics display
│   ├── submit.php       # Rating submission handler
│   ├── download.php     # Data backup endpoint
│   ├── parse.php        # Identifier parsing API
│   ├── bootstrap.php    # Application bootstrap
│   └── style.css        # Stylesheet
├── src/
│   └── functions.php    # Core utility functions
├── conf/                # Per-instance configuration files
│   └── {instance}.yaml
├── data/                # Per-instance data files
│   └── {instance}.yaml
├── locale/              # Localization files
│   ├── en.yaml          # English translations
│   └── cs.yaml          # Czech translations
├── nginx/               # Nginx configuration
└── Dockerfile           # Docker image definition
```

## Quick Start with Docker

```bash
# Pull and run the pre-built image
docker run -d -p 8080:8080 --name rate-app adambajger/rate-anything-webapp:latest

# Access the application
open http://localhost:8080
```

### Development with Volume Mounts

Mount local directories for live editing:

```bash
# Linux/macOS
docker run -d -p 8080:8080 --rm --name rate-app \
  -v "$(pwd)/public:/var/www/html/public:rw" \
  -v "$(pwd)/src:/var/www/html/src:rw" \
  -v "$(pwd)/conf:/var/www/html/conf:rw" \
  -v "$(pwd)/data:/var/www/html/data:rw" \
  -v "$(pwd)/locale:/var/www/html/locale:rw" \
  adambajger/rate-anything-webapp:latest

# Windows PowerShell
docker run -d -p 8080:8080 --rm --name rate-app `
  -v "${PWD}\public:/var/www/html/public:rw" `
  -v "${PWD}\src:/var/www/html/src:rw" `
  -v "${PWD}\conf:/var/www/html/conf:rw" `
  -v "${PWD}\data:/var/www/html/data:rw" `
  -v "${PWD}\locale:/var/www/html/locale:rw" `
  adambajger/rate-anything-webapp:latest
```

### Build from Source

```bash
git clone https://github.com/AdamBajger/rate-anything-webapp.git
cd rate-anything-webapp
docker build -t rate-anything-webapp .
docker run -d -p 8080:8080 --name rate-app rate-anything-webapp
```

## Multi-Instance Support

Run multiple isolated rating systems from a single deployment using the `?instance=` query parameter.

### Setup an Instance

1. Create configuration and data files:
   ```bash
   cp conf/.yaml conf/myinstance.yaml
   cp data/.yaml data/myinstance.yaml
   ```

2. Access the instance:
   ```
   http://localhost:8080/?instance=myinstance
   http://localhost:8080/leaderboard.php?instance=myinstance
   ```

Instance IDs must be alphanumeric (plus `_` and `-`), up to 32 characters.

## Configuration

Each instance has its own configuration file (`conf/{instance}.yaml`):

```yaml
# Rating scale configuration
rating:
  labels:
    -1: "Awful"
    0: "Neutral"
    1: "Excellent"

# Identifier parsing (regex to extract display name from QR codes)
identifier:
  regex: '/^(?:https?:\/\/)?(?:www\.)?(?:example\.com\/)?(.*)$/'
  groups: [1]

# UI customization
ui:
  title: "Rate the Coffee"
  instructions: "Scan a QR code or select an item to rate."
  locale: "en"  # or "cs" for Czech
```

## Data Backup

Download ratings data from the leaderboard page using the "Download Data" button, or directly via:

```
http://localhost:8080/download.php?instance=myinstance
```

This downloads a timestamped YAML file containing all ratings for backup purposes.

## Requirements

- PHP 8.0+
- PHP YAML extension (`php-yaml`)
- Nginx (included in Docker image)
- Modern browser with camera support for QR scanning

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Ratings not saving | Ensure `data/` directory is writable. Check `docker logs rate-app`. |
| Download fails | PHP YAML extension required. The Docker image includes this. |
| Camera not working | Requires HTTPS in production, or localhost for development. |

## License

This project is open source and available under the MIT License.
