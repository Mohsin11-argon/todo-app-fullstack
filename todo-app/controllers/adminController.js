const Task = require('../models/Task');
const User = require('../models/User');
const db = require('../config/db');

exports.createTask = async (req, res) => {
  try {
    const { title, description, assigned_to, priority } = req.body;
    const admin_id = req.user.id;
    const admin_file = req.file ? req.file.filename : null;
    if (!title || !assigned_to) return res.status(400).json({ error: 'Title and assigned_to are required' });

    const t = await Task.create({ title, description, assigned_by: admin_id, assigned_to, priority, admin_file });
    res.json({ ok: true, taskId: t.id });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.getSummary = async (req, res) => {
  try {
    const [counts] = await db.execute(`SELECT status, COUNT(*) AS cnt FROM tasks GROUP BY status`);
    const total = counts.reduce((acc, cur) => acc + cur.cnt, 0);
    const tasks = await Task.listForAdmin(200);
    res.json({ total, counts, tasks });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.listUsers = async (req, res) => {
  try {
    const [rows] = await db.execute('SELECT id, name, email, status, created_at FROM users ORDER BY created_at DESC');
    res.json({ users: rows });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};