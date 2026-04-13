// ============================================================
// Appointment Controller – Book, Cancel, List by Doctor/Patient
// ============================================================
const Appointment = require('../models/Appointment');

const AppointmentController = {
  /**
   * GET /api/appointments
   */
  async getAll(req, res, next) {
    try {
      const filters = {
        specialist_id: req.query.doctor_id,
        parent_id: req.query.patient_id,
        status: req.query.status,
        date: req.query.date,
        limit: req.query.limit,
        offset: req.query.offset
      };
      const appointments = await Appointment.findAll(filters);
      res.json({
        success: true,
        count: appointments.length,
        data: appointments
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * GET /api/appointments/:id
   */
  async getById(req, res, next) {
    try {
      const appointment = await Appointment.findById(req.params.id);
      if (!appointment) {
        return res.status(404).json({
          success: false,
          message: 'Appointment not found.'
        });
      }
      res.json({ success: true, data: appointment });
    } catch (error) {
      next(error);
    }
  },

  /**
   * GET /api/appointments/doctor/:doctorId
   */
  async getByDoctor(req, res, next) {
    try {
      const filters = {
        status: req.query.status,
        date: req.query.date
      };
      const appointments = await Appointment.findByDoctor(req.params.doctorId, filters);
      res.json({
        success: true,
        count: appointments.length,
        data: appointments
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * GET /api/appointments/patient/:patientId
   */
  async getByPatient(req, res, next) {
    try {
      const filters = {
        status: req.query.status
      };
      const appointments = await Appointment.findByPatient(req.params.patientId, filters);
      res.json({
        success: true,
        count: appointments.length,
        data: appointments
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * POST /api/appointments
   * Body: { parent_id, child_id, payment_id, specialist_id, type, scheduled_at }
   */
  async book(req, res, next) {
    try {
      const { parent_id, child_id, payment_id, specialist_id, type, scheduled_at } = req.body;

      if (!parent_id || !payment_id || !specialist_id || !scheduled_at) {
        return res.status(400).json({
          success: false,
          message: 'parent_id, payment_id, specialist_id, and scheduled_at are required.'
        });
      }

      const appointmentId = await Appointment.create({
        parent_id,
        child_id: child_id || null,
        payment_id,
        specialist_id,
        status: 'pending',
        type: type || 'onsite',
        scheduled_at
      });

      const appointment = await Appointment.findById(appointmentId);
      res.status(201).json({
        success: true,
        message: 'Appointment booked successfully.',
        data: appointment
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * PUT /api/appointments/:id
   */
  async update(req, res, next) {
    try {
      const id = req.params.id;
      const appointment = await Appointment.findById(id);
      if (!appointment) {
        return res.status(404).json({
          success: false,
          message: 'Appointment not found.'
        });
      }

      await Appointment.update(id, req.body);
      const updated = await Appointment.findById(id);
      res.json({
        success: true,
        message: 'Appointment updated successfully.',
        data: updated
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * PUT /api/appointments/:id/cancel
   */
  async cancel(req, res, next) {
    try {
      const id = req.params.id;
      const appointment = await Appointment.findById(id);
      if (!appointment) {
        return res.status(404).json({
          success: false,
          message: 'Appointment not found.'
        });
      }

      if (appointment.status === 'cancelled') {
        return res.status(400).json({
          success: false,
          message: 'Appointment is already cancelled.'
        });
      }

      await Appointment.cancel(id);
      res.json({
        success: true,
        message: 'Appointment cancelled successfully.'
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * DELETE /api/appointments/:id
   */
  async delete(req, res, next) {
    try {
      const id = req.params.id;
      const appointment = await Appointment.findById(id);
      if (!appointment) {
        return res.status(404).json({
          success: false,
          message: 'Appointment not found.'
        });
      }

      await Appointment.delete(id);
      res.json({
        success: true,
        message: 'Appointment deleted successfully.'
      });
    } catch (error) {
      next(error);
    }
  }
};

module.exports = AppointmentController;
