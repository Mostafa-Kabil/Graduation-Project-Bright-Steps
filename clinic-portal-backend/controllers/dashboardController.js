// ============================================================
// Dashboard Controller – Clinic Portal Statistics
// ============================================================
const pool = require('../config/db');
const Patient = require('../models/Patient');
const Doctor = require('../models/Doctor');
const Appointment = require('../models/Appointment');
const Clinic = require('../models/Clinic');

const DashboardController = {
  /**
   * GET /api/dashboard/stats
   * Returns aggregated statistics for the clinic dashboard.
   */
  async getStats(req, res, next) {
    try {
      const [totalPatients, totalDoctors, totalAppointments, todayAppointments, totalClinics] =
        await Promise.all([
          Patient.count(),
          Doctor.count(),
          Appointment.count(),
          Appointment.countToday(),
          Clinic.count()
        ]);

      // Appointments by status
      const [statusCounts] = await pool.query(`
        SELECT status, COUNT(*) AS count
        FROM appointment
        GROUP BY status
      `);

      // Recent appointments (last 10)
      const recentAppointments = await Appointment.findAll({ limit: 10 });

      // Monthly appointment trend (last 6 months)
      const [monthlyTrend] = await pool.query(`
        SELECT
          DATE_FORMAT(scheduled_at, '%Y-%m') AS month,
          COUNT(*) AS count
        FROM appointment
        WHERE scheduled_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(scheduled_at, '%Y-%m')
        ORDER BY month ASC
      `);

      res.json({
        success: true,
        data: {
          total_patients: totalPatients,
          total_doctors: totalDoctors,
          total_appointments: totalAppointments,
          daily_appointments: todayAppointments,
          total_clinics: totalClinics,
          appointments_by_status: statusCounts,
          recent_appointments: recentAppointments,
          monthly_trend: monthlyTrend
        }
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * GET /api/dashboard/overview
   * Lightweight summary for quick-loading cards.
   */
  async getOverview(req, res, next) {
    try {
      const [totalPatients, totalDoctors, totalAppointments, todayAppointments] =
        await Promise.all([
          Patient.count(),
          Doctor.count(),
          Appointment.count(),
          Appointment.countToday()
        ]);

      res.json({
        success: true,
        data: {
          total_patients: totalPatients,
          total_doctors: totalDoctors,
          total_appointments: totalAppointments,
          daily_appointments: todayAppointments
        }
      });
    } catch (error) {
      next(error);
    }
  }
};

module.exports = DashboardController;
