// ============================================================
// Patient Model – reads/writes `parent` + `users` + `child`
// ============================================================
const pool = require('../config/db');

const Patient = {
  // --------------- READ ---------------
  async findAll(filters = {}) {
    let sql = `
      SELECT u.user_id, u.first_name, u.last_name, u.email, u.status, u.created_at,
             p.parent_id, p.number_of_children
      FROM parent p
      JOIN users u ON p.parent_id = u.user_id
      WHERE u.role = 'parent'
    `;
    const params = [];

    if (filters.status) {
      sql += ' AND u.status = ?';
      params.push(filters.status);
    }

    sql += ' ORDER BY u.created_at DESC';

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

  async findById(id) {
    const [rows] = await pool.query(`
      SELECT u.user_id, u.first_name, u.last_name, u.email, u.status, u.created_at,
             p.parent_id, p.number_of_children
      FROM parent p
      JOIN users u ON p.parent_id = u.user_id
      WHERE p.parent_id = ?
    `, [id]);
    return rows[0] || null;
  },

  async findChildren(parentId) {
    const [rows] = await pool.query(
      'SELECT * FROM child WHERE parent_id = ? ORDER BY child_id ASC',
      [parentId]
    );
    return rows;
  },

  // --------------- WRITE ---------------
  async create(userId) {
    // User must already exist in `users`. This inserts into `parent`.
    const [result] = await pool.query(
      'INSERT INTO parent (parent_id, number_of_children) VALUES (?, 0)',
      [userId]
    );
    return result.affectedRows > 0;
  },

  async register({ first_name, last_name, email, password }) {
    const conn = await pool.getConnection();
    try {
      await conn.beginTransaction();

      // 1. Insert into users
      const [userResult] = await conn.query(
        'INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)',
        [first_name, last_name, email, password, 'parent', 'active']
      );
      const userId = userResult.insertId;

      // 2. Insert into parent
      await conn.query(
        'INSERT INTO parent (parent_id, number_of_children) VALUES (?, 0)',
        [userId]
      );

      await conn.commit();
      return userId;
    } catch (err) {
      await conn.rollback();
      throw err;
    } finally {
      conn.release();
    }
  },

  async update(id, fields) {
    const allowed = ['first_name', 'last_name', 'email', 'status'];
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
    const conn = await pool.getConnection();
    try {
      await conn.beginTransaction();
      await conn.query('DELETE FROM parent WHERE parent_id = ?', [id]);
      await conn.query('DELETE FROM users WHERE user_id = ?', [id]);
      await conn.commit();
      return true;
    } catch (err) {
      await conn.rollback();
      throw err;
    } finally {
      conn.release();
    }
  },

  // --------------- COUNT ---------------
  async count() {
    const [rows] = await pool.query("SELECT COUNT(*) AS total FROM parent");
    return rows[0].total;
  }
};

module.exports = Patient;
