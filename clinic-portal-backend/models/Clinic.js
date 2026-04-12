// ============================================================
// Clinic Model – reads/writes `clinic` & `clinic_phone` tables
// ============================================================
const pool = require('../config/db');

const Clinic = {
  // --------------- READ ---------------
  async findAll(filters = {}) {
    let sql = `
      SELECT c.clinic_id, c.admin_id, c.clinic_name, c.logo, c.email,
             c.location, c.status, c.rating, c.added_at
      FROM clinic c
      WHERE 1=1
    `;
    const params = [];

    if (filters.status) {
      sql += ' AND c.status = ?';
      params.push(filters.status);
    }

    sql += ' ORDER BY c.added_at DESC';

    if (filters.limit) {
      sql += ' LIMIT ?';
      params.push(parseInt(filters.limit));
    }

    const [rows] = await pool.query(sql, params);
    return rows;
  },

  async findById(id) {
    const [rows] = await pool.query(`
      SELECT c.clinic_id, c.admin_id, c.clinic_name, c.logo, c.email,
             c.location, c.status, c.rating, c.added_at
      FROM clinic c
      WHERE c.clinic_id = ?
    `, [id]);

    if (!rows[0]) return null;

    // Attach phone numbers
    const [phones] = await pool.query(
      'SELECT phone FROM clinic_phone WHERE clinic_id = ?', [id]
    );
    rows[0].phones = phones.map(p => p.phone);

    // Attach doctors
    const [doctors] = await pool.query(`
      SELECT s.specialist_id, s.first_name, s.last_name,
             s.specialization, s.experience_years
      FROM specialist s
      WHERE s.clinic_id = ?
    `, [id]);
    rows[0].doctors = doctors;

    return rows[0];
  },

  // --------------- WRITE ---------------
  async create({ admin_id, clinic_name, logo, email, password, location, status }) {
    const [result] = await pool.query(
      `INSERT INTO clinic (admin_id, clinic_name, logo, email, password, location, status)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [admin_id, clinic_name, logo || null, email, password || null, location || null, status || 'pending']
    );
    return result.insertId;
  },

  async update(id, fields) {
    const allowed = ['clinic_name', 'logo', 'email', 'location', 'status', 'rating'];
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
    const [result] = await pool.query(`UPDATE clinic SET ${sets.join(', ')} WHERE clinic_id = ?`, params);
    return result.affectedRows > 0;
  },

  async delete(id) {
    const [result] = await pool.query('DELETE FROM clinic WHERE clinic_id = ?', [id]);
    return result.affectedRows > 0;
  },

  // --------------- PHONES ---------------
  async addPhone(clinicId, phone) {
    await pool.query('INSERT INTO clinic_phone (clinic_id, phone) VALUES (?, ?)', [clinicId, phone]);
  },

  async removePhone(clinicId, phone) {
    await pool.query('DELETE FROM clinic_phone WHERE clinic_id = ? AND phone = ?', [clinicId, phone]);
  },

  // --------------- COUNTS ---------------
  async count(filters = {}) {
    let sql = 'SELECT COUNT(*) AS total FROM clinic WHERE 1=1';
    const params = [];

    if (filters.status) {
      sql += ' AND status = ?';
      params.push(filters.status);
    }

    const [rows] = await pool.query(sql, params);
    return rows[0].total;
  }
};

module.exports = Clinic;
