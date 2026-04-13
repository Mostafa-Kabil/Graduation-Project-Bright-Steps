// ============================================================
// User Model – reads/writes to `users` table
// ============================================================
const pool = require('../config/db');

const User = {
  // --------------- READ ---------------
  async findByEmail(email) {
    const [rows] = await pool.query('SELECT * FROM users WHERE email = ?', [email]);
    return rows[0] || null;
  },

  async findById(id) {
    const [rows] = await pool.query('SELECT user_id, first_name, last_name, email, role, status, created_at FROM users WHERE user_id = ?', [id]);
    return rows[0] || null;
  },

  async findAll(filters = {}) {
    let sql = 'SELECT user_id, first_name, last_name, email, role, status, created_at FROM users WHERE 1=1';
    const params = [];

    if (filters.role) {
      sql += ' AND role = ?';
      params.push(filters.role);
    }
    if (filters.status) {
      sql += ' AND status = ?';
      params.push(filters.status);
    }

    sql += ' ORDER BY created_at DESC';

    if (filters.limit) {
      sql += ' LIMIT ?';
      params.push(parseInt(filters.limit));
    }
    if (filters.offset) {
      sql += ' OFFSET ?';
      params.push(parseInt(filters.offset));
    }

    const [rows] = await pool.query(sql, params);
    return rows;
  },

  // --------------- WRITE ---------------
  async create({ first_name, last_name, email, password, role, status = 'active' }) {
    const [result] = await pool.query(
      'INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)',
      [first_name, last_name, email, password, role, status]
    );
    return result.insertId;
  },

  async update(id, fields) {
    const allowed = ['first_name', 'last_name', 'email', 'password', 'status'];
    const sets = [];
    const params = [];

    for (const key of allowed) {
      if (fields[key] !== undefined) {
        sets.push(`${key} = ?`);
        params.push(fields[key]);
      }
    }
    if (sets.length === 0) return false;

    params.push(id);
    const [result] = await pool.query(`UPDATE users SET ${sets.join(', ')} WHERE user_id = ?`, params);
    return result.affectedRows > 0;
  },

  async delete(id) {
    const [result] = await pool.query('DELETE FROM users WHERE user_id = ?', [id]);
    return result.affectedRows > 0;
  },

  // --------------- COUNTS ---------------
  async countByRole(role) {
    const [rows] = await pool.query('SELECT COUNT(*) AS total FROM users WHERE role = ?', [role]);
    return rows[0].total;
  }
};

module.exports = User;
