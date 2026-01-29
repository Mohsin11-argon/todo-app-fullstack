require('dotenv').config();
const bcrypt = require('bcrypt');
const db = require('../config/db');

const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'AdminPass123!';

(async () => {
  try {
    const hashed = await bcrypt.hash(ADMIN_PASSWORD, 12);
    const [rows] = await db.execute('SELECT id FROM admins WHERE email = ?', [ADMIN_EMAIL]);
    if (rows.length > 0) {
      console.log('Admin already exists');
      process.exit(0);
    }
    await db.execute('INSERT INTO admins (email, password) VALUES (?, ?)', [ADMIN_EMAIL, hashed]);
    console.log('Admin seeded:', ADMIN_EMAIL);
    process.exit(0);
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
})();