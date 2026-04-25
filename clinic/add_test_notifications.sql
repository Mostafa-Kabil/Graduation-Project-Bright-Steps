-- Add test notifications for clinic users
-- Run this after logging in as a clinic user to see test notifications

-- First, find your clinic user ID
-- SELECT user_id, first_name, last_name, role FROM users WHERE role = 'clinic';

-- Insert test notifications (replace :user_id with your actual user_id)
INSERT INTO notifications (user_id, type, title, message) VALUES
(1, 'system', 'Welcome to Bright Steps', 'Your clinic account has been set up successfully.'),
(1, 'appointment_reminder', 'New Appointment Request', 'You have a new appointment request from a parent.'),
(1, 'general', 'System Update', 'We have updated the clinic dashboard with new features.'),
(1, 'payment_success', 'Payment Received', 'A payment has been processed for your clinic services.');

-- Verify notifications were added
-- SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
