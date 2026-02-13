const express = require('express');
const router = express.Router();
const botUsersController = require('../controllers/botUsers.controller');

router.get('/', botUsersController.getAll);
router.get('/:sessionId', botUsersController.getById);
router.post('/', botUsersController.create);
router.put('/:sessionId', botUsersController.update);
router.delete('/:sessionId', botUsersController.remove);

module.exports = router;
