require('dotenv').config();
const bcrypt = require('bcrypt');
const db = require('../config/db');

(async () => {
  try {
    // Create a test user
    const hashed = await bcrypt.hash('userpass', 12);
    const [existing] = await db.execute('SELECT id FROM users WHERE email = ?', ['user@example.com']);
    if (existing.length === 0) {
      await db.execute('INSERT INTO users (name, email, password) VALUES (?, ?, ?)', ['Test User', 'user@example.com', hashed]);
      console.log('Created test user user@example.com / userpass');
    } else {
      console.log('Test user already exists');
    }

    console.log('Seeding tasks (requires admin id)');
    const [admins] = await db.execute('SELECT id FROM admins LIMIT 1');
    const adminId = admins[0] && admins[0].id;
    const [users] = await db.execute('SELECT id FROM users WHERE email = ?', ['user@example.com']);
    const userId = users[0] && users[0].id;
    if (adminId && userId) {
      await db.execute('INSERT INTO tasks (title, description, assigned_by, assigned_to, priority) VALUES (?, ?, ?, ?, ?)',
        ['Welcome Task', 'This is your first task.', adminId, userId, 'Medium']);
      console.log('Inserted sample task');
    } else {
      console.log('Admin or user missing; run seedAdmin and ensure user exists');
    }

    process.exit(0);
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
})();