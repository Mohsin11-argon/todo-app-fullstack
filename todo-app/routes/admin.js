const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const role = require('../middleware/roles');
const upload = require('../middleware/upload');
const adminCtrl = require('../controllers/adminController');

// Admin protected routes
router.post('/tasks', auth, role('admin'), upload.single('admin_file'), adminCtrl.createTask);
router.get('/summary', auth, role('admin'), adminCtrl.getSummary);
router.get('/users', auth, role('admin'), adminCtrl.listUsers);

module.exports = router;