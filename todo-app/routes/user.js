const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const role = require('../middleware/roles');
const upload = require('../middleware/upload');
const userCtrl = require('../controllers/userController');

router.get('/tasks', auth, role('user'), userCtrl.getMyTasks);
router.post('/tasks/:id/upload', auth, role('user'), upload.single('file'), userCtrl.uploadCompletion);
router.patch('/tasks/:id/status', auth, role('user'), userCtrl.updateStatus);
router.get('/tasks/:id/admin-file', auth, userCtrl.downloadAdminFile); // admin or assigned user

module.exports = router;