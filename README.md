# rate-anything-webapp
This project contains a lightweight web app to streamline simple ratings of anything. It is based on QR codes that identify things.

## Overview

The application uses UUIDs to identify different rating configurations. Each UUID corresponds to a specific rating setup (e.g., restaurant rating, product review, service quality) defined in the `config.yaml` file.

## Usage

### Running the Application

1. Ensure you have PHP 7.4 or higher installed
2. Start the PHP development server:
   ```bash
   php -S localhost:8080
   ```
3. Open your browser and navigate to:
   ```
   http://localhost:8080/index.php?uuid=YOUR-UUID-HERE
   ```

### URL Parameters

- `uuid` - (required) A valid UUID that identifies the rating configuration to load

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
- `categories` - Array of rating categories with weights

### Example Configuration

```yaml
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
```

## Features

- ✅ UUID-based configuration loading from YAML
- ✅ URL-encoded GET request parameter support
- ✅ Input validation (UUID format)
- ✅ Error handling for invalid/missing UUIDs
- ✅ Clean, responsive UI
- ✅ Help page with available configurations

## QR Code Integration

Generate QR codes that encode URLs with specific UUIDs to allow users to quickly access rating forms by scanning the code.
