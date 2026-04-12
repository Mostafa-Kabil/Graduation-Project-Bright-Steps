// ============================================================
// Appointment Model – reads/writes `appointment` table
// ============================================================
const pool = require('../config/db');

const Appointment = {
  // --------------- READ ---------------
  async findAll(filters = {}) {
    let sql = `
      SELECT a.appointment_id, a.parent_id, a.child_id, a.payment_id,
             a.specialist_id, a.status, a.type, a.report, a.comment,
             a.scheduled_at,
             CONCAT(pu.first_name, ' ', pu.last_name) AS parent_name,
             CONCAT(s.first_name, ' ', s.last_name) AS doctor_name,
             s.specialization,
             c.clinic_name
      FROM appointment a
      LEFT JOIN users pu ON a.parent_id = pu.user_id
      LEFT JOIN specialist s ON a.specialist_id = s.specialist_id
      LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
      WHERE 1=1
    `;
    const params = [];

    if (filters.specialist_id) {
      sql += ' AND a.specialist_id = ?';
      params.push(filters.specialist_id);
    }
    if (filters.parent_id) {
      sql += ' AND a.parent_id = ?';
      params.push(filters.parent_id);
    }
    if (filters.status) {
      sql += ' AND a.status = ?';
      params.push(filters.status);
    }
    if (filters.date) {
      sql += ' AND DATE(a.scheduled_at) = ?';
      params.push(filters.date);
    }

    sql += ' ORDER BY a.scheduled_at DESC';

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
      SELECT a.*, 
             CONCAT(pu.first_name, ' ', pu.last_name) AS parent_name,
             pu.email AS parent_email,
             CONCAT(s.first_name, ' ', s.last_name) AS doctor_name,
             s.specialization,
             c.clinic_name, c.location AS clinic_location
      FROM appointment a
      LEFT JOIN users pu ON a.parent_id = pu.user_id
      LEFT JOIN specialist s ON a.specialist_id = s.specialist_id
      LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
      WHERE a.appointment_id = ?
    `, [id]);
    return rows[0] || null;
  },

  async findByDoctor(doctorId, filters = {}) {
    let sql = `
      SELECT a.appointment_id, a.parent_id, a.child_id,
             a.status, a.type, a.report, a.comment, a.scheduled_at,
             CONCAT(pu.first_name, ' ', pu.last_name) AS parent_name
      FROM appointment a
      LEFT JOIN users pu ON a.parent_id = pu.user_id
      WHERE a.specialist_id = ?
    `;
    const params = [doctorId];

    if (filters.status) {
      sql += ' AND a.status = ?';
      params.push(filters.status);
    }
    if (filters.date) {
      sql += ' AND DATE(a.scheduled_at) = ?';
      params.push(filters.date);
    }

    sql += ' ORDER BY a.scheduled_at DESC';
    const [rows] = await pool.query(sql, params);
    return rows;
  },

  async findByPatient(parentId, filters = {}) {
    let sql = `
      SELECT a.appointment_id, a.specialist_id, a.child_id,
             a.status, a.type, a.report, a.comment, a.scheduled_at,
             CONCAT(s.first_name, ' ', s.last_name) AS doctor_name,
             s.specialization, c.clinic_name
      FROM appointment a
      LEFT JOIN specialist s ON a.specialist_id = s.specialist_id
      LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
      WHERE a.parent_id = ?
    `;
    const params = [parentId];

    if (filters.status) {
      sql += ' AND a.status = ?';
      params.push(filters.status);
    }

    sql += ' ORDER BY a.scheduled_at DESC';
    const [rows] = await pool.query(sql, params);
    return rows;
  },

  // --------------- WRITE ---------------
  async create({ parent_id, child_id, payment_id, specialist_id, status, type, scheduled_at }) {
    const [result] = await pool.query(
      `INSERT INTO appointment (parent_id, child_id, payment_id, specialist_id, status, type, scheduled_at)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [parent_id, child_id || null, payment_id, specialist_id, status || 'pending', type || 'onsite', scheduled_at]
    );
    return result.insertId;
  },

  async update(id, fields) {
    const allowed = ['status', 'type', 'report', 'comment', 'scheduled_at'];
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
    const [result] = await pool.query(`UPDATE appointment SET ${sets.join(', ')} WHERE appointment_id = ?`, params);
    return result.affectedRows > 0;
  },

  async cancel(id) {
    const [result] = await pool.query(
      "UPDATE appointment SET status = 'cancelled' WHERE appointment_id = ?",
      [id]
    );
    return result.affectedRows > 0;
  },

  async delete(id) {
    const [result] = await pool.query('DELETE FROM appointment WHERE appointment_id = ?', [id]);
    return result.affectedRows > 0;
  },

  // --------------- COUNTS ---------------
  async count(filters = {}) {
    let sql = 'SELECT COUNT(*) AS total FROM appointment WHERE 1=1';
    const params = [];

    if (filters.status) {
      sql += ' AND status = ?';
      params.push(filters.status);
    }
    if (filters.date) {
      sql += ' AND DATE(scheduled_at) = ?';
      params.push(filters.date);
    }
    if (filters.specialist_id) {
      sql += ' AND specialist_id = ?';
      params.push(filters.specialist_id);
    }

    const [rows] = await pool.query(sql, params);
    return rows[0].total;
  },

  async countToday() {
    const [rows] = await pool.query(
      'SELECT COUNT(*) AS total FROM appointment WHERE DATE(scheduled_at) = CURDATE()'
    );
    return rows[0].total;
  }
};

module.exports = Appointment;
