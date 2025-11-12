const express = require('express');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(express.json());
app.use(express.static('public'));

// In-memory storage for ratings (in a real app, use a database)
const ratings = {};

// API endpoint to submit a rating
app.post('/api/rate', (req, res) => {
  const { itemId, rating } = req.body;
  
  if (!itemId || !rating) {
    return res.status(400).json({ error: 'Item ID and rating are required' });
  }
  
  if (rating < 1 || rating > 5) {
    return res.status(400).json({ error: 'Rating must be between 1 and 5' });
  }
  
  if (!ratings[itemId]) {
    ratings[itemId] = [];
  }
  
  ratings[itemId].push(rating);
  
  const average = ratings[itemId].reduce((a, b) => a + b, 0) / ratings[itemId].length;
  
  res.json({
    success: true,
    itemId,
    rating,
    count: ratings[itemId].length,
    average: average.toFixed(2)
  });
});

// API endpoint to get ratings for an item
app.get('/api/ratings/:itemId', (req, res) => {
  const { itemId } = req.params;
  
  if (!ratings[itemId]) {
    return res.json({
      itemId,
      count: 0,
      average: 0,
      ratings: []
    });
  }
  
  const average = ratings[itemId].reduce((a, b) => a + b, 0) / ratings[itemId].length;
  
  res.json({
    itemId,
    count: ratings[itemId].length,
    average: average.toFixed(2),
    ratings: ratings[itemId]
  });
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Rate Anything app listening on port ${PORT}`);
  console.log(`Access the app at http://localhost:${PORT}`);
});
