// ============================================================
// Patient Controller – Register, Profile, Update, Delete
// ============================================================
const bcrypt = require('bcryptjs');
const Patient = require('../models/Patient');
const User = require('../models/User');

const PatientController = {
  /**
   * GET /api/patients
   */
  async getAll(req, res, next) {
    try {
      const filters = {
        status: req.query.status,
        limit: req.query.limit,
        offset: req.query.offset
      };
      const patients = await Patient.findAll(filters);
      res.json({
        success: true,
        count: patients.length,
        data: patients
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * POST /api/patients/register
   * Body: { first_name, last_name, email, password }
   */
  async register(req, res, next) {
    try {
      const { first_name, last_name, email, password } = req.body;

      if (!first_name || !last_name || !email || !password) {
        return res.status(400).json({
          success: false,
          message: 'first_name, last_name, email, and password are required.'
        });
      }

      const existing = await User.findByEmail(email);
      if (existing) {
        return res.status(409).json({
          success: false,
          message: 'Email already registered.'
        });
      }

      const salt = await bcrypt.genSalt(10);
      const hashedPassword = await bcrypt.hash(password, salt);

      const userId = await Patient.register({
        first_name,
        last_name,
        email,
        password: hashedPassword
      });

      const patient = await Patient.findById(userId);
      res.status(201).json({
        success: true,
        message: 'Patient registered successfully.',
        data: patient
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * GET /api/patients/:id
   */
  async getProfile(req, res, next) {
    try {
      const patient = await Patient.findById(req.params.id);
      if (!patient) {
        return res.status(404).json({
          success: false,
          message: 'Patient not found.'
        });
      }

      // Also fetch children
      const children = await Patient.findChildren(req.params.id);
      patient.children = children;

      res.json({ success: true, data: patient });
    } catch (error) {
      next(error);
    }
  },

  /**
   * PUT /api/patients/:id
   */
  async update(req, res, next) {
    try {
      const id = req.params.id;
      const patient = await Patient.findById(id);
      if (!patient) {
        return res.status(404).json({
          success: false,
          message: 'Patient not found.'
        });
      }

      await Patient.update(id, req.body);
      const updated = await Patient.findById(id);
      res.json({
        success: true,
        message: 'Patient updated successfully.',
        data: updated
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * DELETE /api/patients/:id
   */
  async delete(req, res, next) {
    try {
      const id = req.params.id;
      const patient = await Patient.findById(id);
      if (!patient) {
        return res.status(404).json({
          success: false,
          message: 'Patient not found.'
        });
      }

      await Patient.delete(id);
      res.json({
        success: true,
        message: 'Patient deleted successfully.'
      });
    } catch (error) {
      next(error);
    }
  }
};

module.exports = PatientController;
