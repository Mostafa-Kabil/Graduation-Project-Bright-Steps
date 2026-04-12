// ============================================================
// Doctor Controller – CRUD for doctors / specialists
// ============================================================
const bcrypt = require('bcryptjs');
const Doctor = require('../models/Doctor');
const User = require('../models/User');

const DoctorController = {
  /**
   * GET /api/doctors
   */
  async getAll(req, res, next) {
    try {
      const filters = {
        clinic_id: req.query.clinic_id,
        specialization: req.query.specialization,
        limit: req.query.limit,
        offset: req.query.offset
      };
      const doctors = await Doctor.findAll(filters);
      res.json({
        success: true,
        count: doctors.length,
        data: doctors
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * GET /api/doctors/:id
   */
  async getById(req, res, next) {
    try {
      const doctor = await Doctor.findById(req.params.id);
      if (!doctor) {
        return res.status(404).json({
          success: false,
          message: 'Doctor not found.'
        });
      }
      res.json({ success: true, data: doctor });
    } catch (error) {
      next(error);
    }
  },

  /**
   * POST /api/doctors
   * Body: { first_name, last_name, email, password, clinic_id, specialization,
   *         certificate_of_experience, experience_years }
   */
  async create(req, res, next) {
    try {
      const {
        first_name, last_name, email, password,
        clinic_id, specialization,
        certificate_of_experience, experience_years
      } = req.body;

      if (!first_name || !last_name || !email || !password || !clinic_id) {
        return res.status(400).json({
          success: false,
          message: 'first_name, last_name, email, password, and clinic_id are required.'
        });
      }

      // Check duplicate email
      const existing = await User.findByEmail(email);
      if (existing) {
        return res.status(409).json({
          success: false,
          message: 'Email already registered.'
        });
      }

      // 1. Create user record
      const salt = await bcrypt.genSalt(10);
      const hashedPassword = await bcrypt.hash(password, salt);

      const userId = await User.create({
        first_name,
        last_name,
        email,
        password: hashedPassword,
        role: 'doctor',
        status: 'active'
      });

      // 2. Create specialist record (specialist_id = user_id via FK)
      await Doctor.create({
        specialist_id: userId,
        clinic_id,
        first_name,
        last_name,
        specialization: specialization || null,
        certificate_of_experience: certificate_of_experience || null,
        experience_years: experience_years || null
      });

      const doctor = await Doctor.findById(userId);
      res.status(201).json({
        success: true,
        message: 'Doctor created successfully.',
        data: doctor
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * PUT /api/doctors/:id
   */
  async update(req, res, next) {
    try {
      const id = req.params.id;
      const doctor = await Doctor.findById(id);
      if (!doctor) {
        return res.status(404).json({
          success: false,
          message: 'Doctor not found.'
        });
      }

      // Update specialist fields
      const specialistUpdated = await Doctor.update(id, req.body);

      // Update user fields (first_name, last_name, email, status)
      const userFields = {};
      if (req.body.first_name) userFields.first_name = req.body.first_name;
      if (req.body.last_name) userFields.last_name = req.body.last_name;
      if (req.body.email) userFields.email = req.body.email;
      if (req.body.status) userFields.status = req.body.status;

      if (Object.keys(userFields).length > 0) {
        await User.update(id, userFields);
      }

      const updated = await Doctor.findById(id);
      res.json({
        success: true,
        message: 'Doctor updated successfully.',
        data: updated
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * DELETE /api/doctors/:id
   */
  async delete(req, res, next) {
    try {
      const id = req.params.id;
      const doctor = await Doctor.findById(id);
      if (!doctor) {
        return res.status(404).json({
          success: false,
          message: 'Doctor not found.'
        });
      }

      await Doctor.delete(id);
      res.json({
        success: true,
        message: 'Doctor deleted successfully.'
      });
    } catch (error) {
      next(error);
    }
  }
};

module.exports = DoctorController;
