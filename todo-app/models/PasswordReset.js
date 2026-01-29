const db = require('../config/db');

const PasswordReset = {
  async create(user_id, token, expires_at) {
    const [result] = await db.execute('INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (?, ?, ?)', [user_id, token, expires_at]);
    return { id: result.insertId };
  },
  async findByToken(token) {
    const [rows] = await db.execute('SELECT * FROM password_resets WHERE reset_token = ?', [token]);
    return rows[0] || null;
  },
  async deleteById(id) {
    const [res] = await db.execute('DELETE FROM password_resets WHERE id = ?', [id]);
    return res.affectedRows > 0;
  }
};

module.exports = PasswordReset;