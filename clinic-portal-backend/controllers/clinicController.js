// ============================================================
// Clinic Controller – CRUD for clinics
// ============================================================
const Clinic = require('../models/Clinic');

const ClinicController = {
  /**
   * GET /api/clinics
   */
  async getAll(req, res, next) {
    try {
      const filters = {
        status: req.query.status,
        limit: req.query.limit
      };
      const clinics = await Clinic.findAll(filters);
      res.json({
        success: true,
        count: clinics.length,
        data: clinics
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * GET /api/clinics/:id
   */
  async getById(req, res, next) {
    try {
      const clinic = await Clinic.findById(req.params.id);
      if (!clinic) {
        return res.status(404).json({
          success: false,
          message: 'Clinic not found.'
        });
      }
      res.json({ success: true, data: clinic });
    } catch (error) {
      next(error);
    }
  },

  /**
   * POST /api/clinics
   */
  async create(req, res, next) {
    try {
      const { admin_id, clinic_name, logo, email, password, location, status, phones } = req.body;

      if (!admin_id || !clinic_name) {
        return res.status(400).json({
          success: false,
          message: 'admin_id and clinic_name are required.'
        });
      }

      const clinicId = await Clinic.create({
        admin_id, clinic_name, logo, email, password, location, status
      });

      // Insert phone numbers if provided
      if (phones && Array.isArray(phones)) {
        for (const phone of phones) {
          await Clinic.addPhone(clinicId, phone);
        }
      }

      const clinic = await Clinic.findById(clinicId);
      res.status(201).json({
        success: true,
        message: 'Clinic created successfully.',
        data: clinic
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * PUT /api/clinics/:id
   */
  async update(req, res, next) {
    try {
      const id = req.params.id;
      const clinic = await Clinic.findById(id);
      if (!clinic) {
        return res.status(404).json({
          success: false,
          message: 'Clinic not found.'
        });
      }

      await Clinic.update(id, req.body);
      const updated = await Clinic.findById(id);
      res.json({
        success: true,
        message: 'Clinic updated successfully.',
        data: updated
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * DELETE /api/clinics/:id
   */
  async delete(req, res, next) {
    try {
      const id = req.params.id;
      const clinic = await Clinic.findById(id);
      if (!clinic) {
        return res.status(404).json({
          success: false,
          message: 'Clinic not found.'
        });
      }

      await Clinic.delete(id);
      res.json({
        success: true,
        message: 'Clinic deleted successfully.'
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * POST /api/clinics/:id/phones
   * Body: { phone }
   */
  async addPhone(req, res, next) {
    try {
      const { phone } = req.body;
      if (!phone) {
        return res.status(400).json({
          success: false,
          message: 'Phone number is required.'
        });
      }
      await Clinic.addPhone(req.params.id, phone);
      res.status(201).json({
        success: true,
        message: 'Phone number added.'
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * DELETE /api/clinics/:id/phones/:phone
   */
  async removePhone(req, res, next) {
    try {
      await Clinic.removePhone(req.params.id, req.params.phone);
      res.json({
        success: true,
        message: 'Phone number removed.'
      });
    } catch (error) {
      next(error);
    }
  }
};

module.exports = ClinicController;
