# Rate Anything Web Application

A lightweight web application for rating items using QR code scanning. The application provides a simple, responsive interface for collecting and displaying ratings with persistent storage in YAML format.

## Overview

This application enables users to scan QR codes or manually select items to rate them on a configurable scale (default 1-5). All ratings are stored persistently in YAML files and displayed in a leaderboard with detailed statistics. The design is modular and follows best practices for potential future migration to database storage.

## Architecture

The application follows a small, opinionated structure to separate public assets from application logic:
- `public/`: webroot — contains entry points and static assets served by the webserver (e.g. `index.php`, `leaderboard.php`, `submit.php`, `download.php`, `style.css`).
- `src/`: application code — PHP helpers, functions and business logic (not directly web-accessible).
- `config.yaml` and `data.yaml`: configuration and persistent YAML storage at the project root.

Benefits: clearer security boundary (only `public/` is served), easier bind-mounting for development, and simpler Docker image builds.

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
- Web server (nginx)
- Modern web browser with camera support for QR scanning

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

# Rate Anything Webapp — concise

A small PHP app to scan or enter item identifiers and collect ratings stored in YAML files. Designed for easy local development with Docker and simple per-instance configuration.

Key points
- Webroot: `public/` (entrypoints: `index.php`, `leaderboard.php`, `submit.php`, `download.php`) 
- App code: `src/`
- Per-instance files: `conf/{id}.yaml` and `data/{id}.yaml` (selected with `?instance={id}`)
- Default fallback: project-level `config.yaml` and `data.yaml` when no `instance` is provided
- Download endpoint (`download.php`) requires the PHP YAML extension (`yaml_emit`)

Quick Docker (development)

PowerShell (Windows) — recommended for interactive development (keeps your original command):

```powershell
docker run -d -p 8080:80 --rm --name rate-app `
  -v "${PWD}\public:/var/www/html/public:rw" `
  -v "${PWD}\src:/var/www/html/src:rw" `
  -v "${PWD}\conf:/var/www/html/conf:rw" `
  -v "${PWD}\data:/var/www/html/data:rw" `
  adambajger/rate-anything-webapp:1.0.0
```

Notes
- To use an instance, create `conf/{id}.yaml` and `data/{id}.yaml` (you can copy top-level YAMLs):
  ```powershell
  copy config.yaml conf\cofirat1.yaml
  copy data.yaml data\cofirat1.yaml
  ```
- Visit `http://localhost:8080/?instance=cofirat1` (or append `?instance=` to other pages).
- The `instance` value is validated (alphanumeric, `_` and `-`, up to 32 chars) to avoid unsafe paths.
- If `conf/{id}.yaml` or `data/{id}.yaml` are missing, the app falls back to `config.yaml`/`data.yaml`.

Requirements
- PHP 8+
- (Optional but recommended) PHP YAML extension (`php-yaml`) for full YAML emit/parsing and the download endpoint
- Modern browser for QR scanning (camera access)

File layout (relevant)

```
rate-anything-webapp/
├── public/           # Webroot (served by nginx/apache)
├── src/              # Application logic
├── conf/             # Optional per-instance config files
├── data/             # Optional per-instance data files
├── config.yaml       # Default config
└── data.yaml         # Default data
```

Troubleshooting
- Ratings not appearing: ensure the container has `data/` mounted and writable. Check `docker logs rate-app`.
- Download fails with 'PHP YAML extension is required': install `php-yaml` in the image or host PHP.

If you'd like, I can also add a one-line CLI/admin script to create instance YAML files safely.
  --name rate-app rate-anything-webapp
