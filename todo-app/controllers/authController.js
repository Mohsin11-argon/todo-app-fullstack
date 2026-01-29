const { body, validationResult } = require('express-validator');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const crypto = require('crypto');
require('dotenv').config();

const Admin = require('../models/Admin');
const User = require('../models/User');
const PasswordReset = require('../models/PasswordReset');
const transporter = require('../config/mailer');
const db = require('../config/db');

const JWT_SECRET = process.env.JWT_SECRET || 'secret';
const JWT_EXPIRES_IN = process.env.JWT_EXPIRES_IN || '7d';
const FRONTEND_URL = process.env.FRONTEND_URL || 'http://localhost:3000';

exports.signupValidators = [
  body('name').notEmpty(),
  body('email').isEmail(),
  body('password').isLength({ min: 6 })
];

exports.signup = async (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) return res.status(422).json({ errors: errors.array() });

  const { name, email, password } = req.body;
  try {
    const existing = await User.findByEmail(email);
    if (existing) return res.status(400).json({ error: 'Email already registered' });
    const hashed = await bcrypt.hash(password, 12);
    const user = await User.create(name, email, hashed);
    res.json({ ok: true, user });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.login = async (req, res) => {
  const { email, password } = req.body;
  if (!email || !password) return res.status(400).json({ error: 'Email and password required' });
  try {
    // Try admin first
    let user = await Admin.findByEmail(email);
    let role = 'admin';
    if (!user) {
      user = await User.findByEmail(email);
      role = 'user';
    }
    if (!user) return res.status(400).json({ error: 'Invalid credentials' });
    const ok = await bcrypt.compare(password, user.password);
    if (!ok) return res.status(400).json({ error: 'Invalid credentials' });
    const token = jwt.sign({ id: user.id, role }, JWT_SECRET, { expiresIn: JWT_EXPIRES_IN });
    res.json({ token, user: { id: user.id, email: user.email, name: user.name }, role });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.forgot = async (req, res) => {
  const { email } = req.body;
  if (!email) return res.status(400).json({ error: 'Email required' });
  try {
    const user = await User.findByEmail(email);
    if (!user) return res.status(400).json({ error: 'No user found with this email' });

    const token = crypto.randomBytes(32).toString('hex');
    const expires_at = new Date(Date.now() + 1000 * 60 * 60).toISOString().slice(0, 19).replace('T', ' '); // 1 hour

    await PasswordReset.create(user.id, token, expires_at);

    const resetLink = `${FRONTEND_URL}/reset-password?token=${token}`;

    await transporter.sendMail({
      from: 'no-reply@todo-app.local',
      to: user.email,
      subject: 'Password reset request',
      text: `Use this link to reset your password: ${resetLink}`,
      html: `<p>Use this link to reset your password:</p><p><a href="${resetLink}">${resetLink}</a></p>`
    });

    res.json({ ok: true, message: 'Reset link sent (check Mailtrap)' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.reset = async (req, res) => {
  const { token, password } = req.body;
  if (!token || !password) return res.status(400).json({ error: 'Token and new password required' });
  try {
    const rec = await PasswordReset.findByToken(token);
    if (!rec) return res.status(400).json({ error: 'Invalid token' });

    if (new Date(rec.expires_at) < new Date()) {
      await PasswordReset.deleteById(rec.id);
      return res.status(400).json({ error: 'Token expired' });
    }

    const hashed = await bcrypt.hash(password, 12);
    await db.execute('UPDATE users SET password = ? WHERE id = ?', [hashed, rec.user_id]);
    await PasswordReset.deleteById(rec.id);
    res.json({ ok: true, message: 'Password reset successful' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.me = async (req, res) => {
  try {
    if (req.user.role === 'admin') {
      const admin = await Admin.findById(req.user.id);
      return res.json({ user: admin, role: 'admin' });
    }
    const user = await User.findById(req.user.id);
    return res.json({ user, role: 'user' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};