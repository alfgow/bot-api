const express = require('express');
const router = express.Router();
const chatHistoriesController = require('../controllers/chatHistories.controller');

// Specific routes MUST come before parameterized routes
router.get('/session/:sessionId', chatHistoriesController.getBySessionId);
router.delete('/session/:sessionId', chatHistoriesController.removeBySessionId);

// Generic CRUD routes
router.get('/', chatHistoriesController.getAll);
router.get('/:id', chatHistoriesController.getById);
router.post('/', chatHistoriesController.create);
router.put('/:id', chatHistoriesController.update);
router.delete('/:id', chatHistoriesController.remove);

module.exports = router;
