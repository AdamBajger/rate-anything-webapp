# rate-anything-webapp
This project contains a lightweight web app to streamline simple ratings of anything. It is based on qr codes that identify things.

## Features
- Simple and intuitive rating interface
- Rate items using 1-5 star system
- View average ratings and total count
- QR code-based item identification
- In-memory storage (for demo purposes)

## Running with Docker

### Option 1: Using Docker Compose (Recommended)
```bash
docker-compose up
```

To run in detached mode:
```bash
docker-compose up -d
```

To stop:
```bash
docker-compose down
```

### Option 2: Using Docker directly

#### Build the Docker image
```bash
docker build -t rate-anything-webapp .
```

#### Run the container
```bash
docker run -p 3000:3000 rate-anything-webapp
```

The app will be available at `http://localhost:3000`

#### Run in detached mode
```bash
docker run -d -p 3000:3000 --name rate-app rate-anything-webapp
```

#### Stop the container
```bash
docker stop rate-app
docker rm rate-app
```

## Running without Docker

### Prerequisites
- Node.js 18 or higher

### Installation
```bash
npm install
```

### Start the server
```bash
npm start
```

The app will be available at `http://localhost:3000`

## How to Use
1. Enter an Item ID (e.g., a QR code identifier like "ITEM-001")
2. Select a rating from 1 to 5 stars
3. Click "Submit Rating" to record your rating
4. Click "View Ratings" to see the average rating and total count for any item

## API Endpoints

The application provides RESTful API endpoints:

### Submit a Rating
```bash
POST /api/rate
Content-Type: application/json

{
  "itemId": "ITEM-001",
  "rating": 5
}
```

Response:
```json
{
  "success": true,
  "itemId": "ITEM-001",
  "rating": 5,
  "count": 1,
  "average": "5.00"
}
```

### Get Ratings for an Item
```bash
GET /api/ratings/:itemId
```

Response:
```json
{
  "itemId": "ITEM-001",
  "count": 1,
  "average": "5.00",
  "ratings": [5]
}
```

## Technology Stack
- **Backend**: Node.js with Express.js
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Storage**: In-memory (for demo purposes)
- **Containerization**: Docker
