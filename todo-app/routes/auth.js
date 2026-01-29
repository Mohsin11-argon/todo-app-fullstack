const express = require('express');
const router = express.Router();
const authCtrl = require('../controllers/authController');
const authMiddleware = require('../middleware/auth');

router.post('/signup', authCtrl.signupValidators, authCtrl.signup);
router.post('/login', authCtrl.login);
router.post('/forgot', authCtrl.forgot);
router.post('/reset', authCtrl.reset);
router.get('/me', authMiddleware, authCtrl.me);

module.exports = router;