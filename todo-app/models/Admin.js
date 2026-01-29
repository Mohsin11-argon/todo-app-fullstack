const db = require('../config/db');

const Admin = {
  async findByEmail(email) {
    const [rows] = await db.execute('SELECT id, email, password, created_at FROM admins WHERE email = ?', [email]);
    return rows[0] || null;
  },
  async findById(id) {
    const [rows] = await db.execute('SELECT id, email, created_at FROM admins WHERE id = ?', [id]);
    return rows[0] || null;
  },
  async create(email, hashedPassword) {
    const [result] = await db.execute('INSERT INTO admins (email, password) VALUES (?, ?)', [email, hashedPassword]);
    return { id: result.insertId, email };
  }
};

module.exports = Admin;