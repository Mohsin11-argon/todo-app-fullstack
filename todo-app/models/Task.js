const db = require('../config/db');

const Task = {
  async create({ title, description, assigned_by, assigned_to, priority = 'Medium', admin_file = null }) {
    const [result] = await db.execute(
      'INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, admin_file) VALUES (?, ?, ?, ?, ?, ?)',
      [title, description, assigned_by, assigned_to, priority, admin_file]
    );
    return { id: result.insertId };
  },

  async updateStatus(id, status) {
    if (status === 'completed') {
      const [res] = await db.execute('UPDATE tasks SET status = ?, completed_at = NOW() WHERE id = ?', [status, id]);
      return res.affectedRows > 0;
    }
    const [res] = await db.execute('UPDATE tasks SET status = ? WHERE id = ?', [status, id]);
    return res.affectedRows > 0;
  },

  async attachUserFile(id, filename) {
    const [res] = await db.execute('UPDATE tasks SET user_file = ?, status = ? WHERE id = ?', [filename, 'completed', id]);
    return res.affectedRows > 0;
  },

  async findById(id) {
    const [rows] = await db.execute('SELECT * FROM tasks WHERE id = ?', [id]);
    return rows[0] || null;
  },

  async listForAdmin(limit = 100) {
    const [rows] = await db.execute(
      `SELECT t.*, u.name AS assignee, a.email AS assigned_by_email FROM tasks t
       JOIN users u ON u.id = t.assigned_to
       JOIN admins a ON a.id = t.assigned_by
       ORDER BY t.created_at DESC
       LIMIT ?`,
      [limit]
    );
    return rows;
  },

  async listForUser(userId) {
    const [rows] = await db.execute('SELECT * FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC', [userId]);
    return rows;
  }
};

module.exports = Task;