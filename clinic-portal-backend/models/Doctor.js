// ============================================================
// Doctor (Specialist) Model – reads/writes `specialist` + `users`
// ============================================================
const pool = require('../config/db');

const Doctor = {
  // --------------- READ ---------------
  async findAll(filters = {}) {
    let sql = `
      SELECT s.specialist_id, s.clinic_id, s.first_name, s.last_name,
             s.specialization, s.certificate_of_experience,
             s.experience_years, s.created_at,
             u.email, u.status, u.role,
             c.clinic_name
      FROM specialist s
      JOIN users u ON s.specialist_id = u.user_id
      LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
      WHERE 1=1
    `;
    const params = [];

    if (filters.clinic_id) {
      sql += ' AND s.clinic_id = ?';
      params.push(filters.clinic_id);
    }
    if (filters.specialization) {
      sql += ' AND s.specialization LIKE ?';
      params.push(`%${filters.specialization}%`);
    }

    sql += ' ORDER BY s.created_at DESC';

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
      SELECT s.specialist_id, s.clinic_id, s.first_name, s.last_name,
             s.specialization, s.certificate_of_experience,
             s.experience_years, s.created_at,
             u.email, u.status, u.role,
             c.clinic_name, c.location AS clinic_location
      FROM specialist s
      JOIN users u ON s.specialist_id = u.user_id
      LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
      WHERE s.specialist_id = ?
    `, [id]);
    return rows[0] || null;
  },

  // --------------- WRITE ---------------
  async create({ specialist_id, clinic_id, first_name, last_name, specialization, certificate_of_experience, experience_years }) {
    const [result] = await pool.query(
      `INSERT INTO specialist
       (specialist_id, clinic_id, first_name, last_name, specialization, certificate_of_experience, experience_years)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [specialist_id, clinic_id, first_name, last_name, specialization, certificate_of_experience || null, experience_years || null]
    );
    return result.affectedRows > 0;
  },

  async update(id, fields) {
    const allowed = ['clinic_id', 'first_name', 'last_name', 'specialization', 'certificate_of_experience', 'experience_years'];
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
    const [result] = await pool.query(`UPDATE specialist SET ${sets.join(', ')} WHERE specialist_id = ?`, params);
    return result.affectedRows > 0;
  },

  async delete(id) {
    // Delete from specialist first, then from users (cascade handled by FK)
    const [result] = await pool.query('DELETE FROM specialist WHERE specialist_id = ?', [id]);
    if (result.affectedRows > 0) {
      await pool.query('DELETE FROM users WHERE user_id = ?', [id]);
    }
    return result.affectedRows > 0;
  },

  // --------------- COUNT ---------------
  async count(filters = {}) {
    let sql = 'SELECT COUNT(*) AS total FROM specialist s WHERE 1=1';
    const params = [];

    if (filters.clinic_id) {
      sql += ' AND s.clinic_id = ?';
      params.push(filters.clinic_id);
    }

    const [rows] = await pool.query(sql, params);
    return rows[0].total;
  }
};

module.exports = Doctor;
