const express = require('express');
const router = express.Router();
const authController = require('../controllers/auth.controller');
const { authenticate } = require('../middleware/auth');

// Public - login
router.post('/login', authController.login);

// Protected - only authenticated users can register new users
router.post('/register', authenticate, authController.register);

module.exports = router;
