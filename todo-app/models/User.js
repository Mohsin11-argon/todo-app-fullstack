const db = require('../config/db');

const User = {
  async create(name, email, hashedPassword) {
    const [result] = await db.execute('INSERT INTO users (name, email, password) VALUES (?, ?, ?)', [name, email, hashedPassword]);
    return { id: result.insertId, name, email };
  },
  async findByEmail(email) {
    const [rows] = await db.execute('SELECT id, name, email, password, status, created_at FROM users WHERE email = ?', [email]);
    return rows[0] || null;
  },
  async findById(id) {
    const [rows] = await db.execute('SELECT id, name, email, status, created_at FROM users WHERE id = ?', [id]);
    return rows[0] || null;
  }
};

module.exports = User;