// ============================================================
// Auth Controller – Login / Logout / Me
// ============================================================
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const User = require('../models/User');
require('dotenv').config();

const AuthController = {
  /**
   * POST /api/auth/login
   * Body: { email, password }
   */
  async login(req, res, next) {
    try {
      const { email, password } = req.body;

      if (!email || !password) {
        return res.status(400).json({
          success: false,
          message: 'Email and password are required.'
        });
      }

      // 1. Find user
      const user = await User.findByEmail(email);
      if (!user) {
        return res.status(401).json({
          success: false,
          message: 'Invalid email or password.'
        });
      }

      // 2. Check status
      if (user.status !== 'active') {
        return res.status(403).json({
          success: false,
          message: 'Account is not active. Please contact support.'
        });
      }

      // 3. Verify password (bcrypt hashed passwords in DB)
      const isMatch = await bcrypt.compare(password, user.password);
      if (!isMatch) {
        return res.status(401).json({
          success: false,
          message: 'Invalid email or password.'
        });
      }

      // 4. Generate JWT
      const payload = {
        user_id: user.user_id,
        email: user.email,
        role: user.role,
        first_name: user.first_name,
        last_name: user.last_name
      };

      const token = jwt.sign(payload, process.env.JWT_SECRET, {
        expiresIn: process.env.JWT_EXPIRES_IN || '24h'
      });

      res.json({
        success: true,
        message: 'Login successful.',
        data: {
          token,
          user: {
            user_id: user.user_id,
            first_name: user.first_name,
            last_name: user.last_name,
            email: user.email,
            role: user.role,
            status: user.status
          }
        }
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * POST /api/auth/logout
   * (Client-side token discard; optionally blacklist token)
   */
  async logout(req, res, next) {
    try {
      // In a stateless JWT approach the client simply discards the token.
      // For extra security we could blacklist the token – but the `token_blacklist`
      // table already exists in the DB for that purpose.
      res.json({
        success: true,
        message: 'Logged out successfully. Please discard your token.'
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * GET /api/auth/me   (Protected)
   * Returns the currently authenticated user's profile.
   */
  async me(req, res, next) {
    try {
      const user = await User.findById(req.user.user_id);
      if (!user) {
        return res.status(404).json({
          success: false,
          message: 'User not found.'
        });
      }

      res.json({
        success: true,
        data: user
      });
    } catch (error) {
      next(error);
    }
  },

  /**
   * POST /api/auth/register
   * Body: { first_name, last_name, email, password, role }
   */
  async register(req, res, next) {
    try {
      const { first_name, last_name, email, password, role } = req.body;

      if (!first_name || !last_name || !email || !password) {
        return res.status(400).json({
          success: false,
          message: 'first_name, last_name, email, and password are required.'
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

      // Hash password
      const salt = await bcrypt.genSalt(10);
      const hashedPassword = await bcrypt.hash(password, salt);

      const userId = await User.create({
        first_name,
        last_name,
        email,
        password: hashedPassword,
        role: role || 'parent',
        status: 'active'
      });

      // Generate token for auto-login
      const token = jwt.sign(
        { user_id: userId, email, role: role || 'parent', first_name, last_name },
        process.env.JWT_SECRET,
        { expiresIn: process.env.JWT_EXPIRES_IN || '24h' }
      );

      res.status(201).json({
        success: true,
        message: 'Registration successful.',
        data: { user_id: userId, token }
      });
    } catch (error) {
      next(error);
    }
  }
};

module.exports = AuthController;
