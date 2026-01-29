const Task = require('../models/Task');
const path = require('path');

exports.getMyTasks = async (req, res) => {
  try {
    const userId = req.user.id;
    const tasks = await Task.listForUser(userId);
    res.json({ tasks });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.uploadCompletion = async (req, res) => {
  try {
    const userId = req.user.id;
    const taskId = parseInt(req.params.id, 10);
    const file = req.file;
    if (!file) return res.status(400).json({ error: 'File required' });

    const task = await Task.findById(taskId);
    if (!task) return res.status(404).json({ error: 'Task not found' });
    if (task.assigned_to !== userId) return res.status(403).json({ error: 'Not allowed' });

    await Task.attachUserFile(taskId, file.filename);
    res.json({ ok: true });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.updateStatus = async (req, res) => {
  try {
    const userId = req.user.id;
    const taskId = parseInt(req.params.id, 10);
    const { status } = req.body;
    if (!['pending', 'in_progress', 'completed'].includes(status)) return res.status(400).json({ error: 'Invalid status' });

    const task = await Task.findById(taskId);
    if (!task) return res.status(404).json({ error: 'Task not found' });
    if (task.assigned_to !== userId) return res.status(403).json({ error: 'Not allowed' });

    await Task.updateStatus(taskId, status);
    res.json({ ok: true });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.downloadAdminFile = async (req, res) => {
  try {
    const userId = req.user.id;
    const taskId = parseInt(req.params.id, 10);
    const task = await Task.findById(taskId);
    if (!task) return res.status(404).json({ error: 'Task not found' });
    // Only assigned user or admin can download
    if (req.user.role !== 'admin' && task.assigned_to !== userId) return res.status(403).json({ error: 'Forbidden' });

    if (!task.admin_file) return res.status(404).json({ error: 'No admin file' });
    const filePath = path.join(process.cwd(), 'uploads', task.admin_file);
    res.download(filePath);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};