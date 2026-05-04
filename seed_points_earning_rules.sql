-- ============================================================================
-- Bright Steps - Points Earning Rules Seed Data
-- ============================================================================
-- Run this after migration_points_redemption_system.sql
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- Clear existing rules if any
DELETE FROM points_earning_rules;

-- ============================================================================
-- DAILY ACTIONS (with daily caps)
-- ============================================================================

-- Daily login streak (capped to once per day)
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('daily_login', 'Daily Login', 10, 10, 70, 1440, 0, 'Log in to your parent account once per day');

-- ============================================================================
-- CHILD ACTIVITY ACTIONS (with reasonable caps)
-- ============================================================================

-- Growth measurement (height, weight, head circumference)
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('log_growth', 'Log Growth Measurement', 25, 25, 100, 1440, 0, 'Record your child''s height, weight, or head circumference');

-- Voice/speech sample recording
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('record_speech', 'Record Speech Sample', 15, 45, 200, 60, 0, 'Record your child speaking for AI analysis');

-- Milestone achievement logging
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('log_milestone', 'Log Milestone Achievement', 30, 90, 300, 30, 0, 'Mark a developmental milestone as achieved');

-- Motor skill activity completion
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('complete_motor_activity', 'Complete Motor Skill Activity', 20, 60, 250, 30, 0, 'Finish a guided motor skill exercise');

-- ============================================================================
-- WEEKLY ACTIONS (higher points, weekly caps)
-- ============================================================================

-- Weekly goal completion
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('weekly_goal', 'Complete Weekly Goal', 100, NULL, 100, 10080, 0, 'Achieve all weekly development goals');

-- Appointment attendance
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('attend_appointment', 'Attend Scheduled Appointment', 50, 50, 100, 1440, 0, 'Complete a scheduled specialist appointment');

-- Feedback submission after appointment
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('submit_feedback', 'Submit Appointment Feedback', 20, 40, 80, 60, 0, 'Provide feedback after an appointment');

-- ============================================================================
-- MONTHLY/SPECIAL ACTIONS (high value, monthly considerations)
-- ============================================================================

-- Profile completion (one-time)
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('complete_profile', 'Complete Parent Profile', 100, 100, NULL, 0, 0, 'Fill out all parent profile fields');

-- Child profile completion (per child, one-time)
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('complete_child_profile', 'Complete Child Profile', 75, 150, NULL, 0, 0, 'Fill out complete child profile with all details');

-- Referral bonus (requires verification)
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('refer_parent', 'Refer Another Parent', 200, NULL, NULL, 0, 1, 'Successfully refer another parent to join Bright Steps');

-- ============================================================================
-- ENGAGEMENT ACTIONS
-- ============================================================================

-- Article read (educational content engagement)
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('read_article', 'Read Educational Article', 5, 25, 100, 5, 0, 'Read a parenting or child development article');

-- Activity completion from recommendations
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('complete_activity', 'Complete Recommended Activity', 35, 70, 200, 30, 0, 'Finish a recommended developmental activity');

-- ============================================================================
-- ACHIEVEMENT-BASED ACTIONS (no caps, milestone driven)
-- ============================================================================

-- Streak milestones
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('streak_7day', '7-Day Login Streak', 50, NULL, NULL, 0, 0, 'Maintain a 7-day login streak');

INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('streak_30day', '30-Day Login Streak', 250, NULL, NULL, 0, 0, 'Maintain a 30-day login streak');

INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('streak_100day', '100-Day Login Streak', 1000, NULL, NULL, 0, 0, 'Maintain a 100-day login streak');

-- ============================================================================
-- CONTENT CONTRIBUTION (requires verification)
-- ============================================================================

-- Share success story
INSERT INTO points_earning_rules (action_key, action_name, points_value, daily_cap, weekly_cap, cooldown_minutes, requires_verification, description) VALUES
('share_story', 'Share Success Story', 100, 100, 200, 10080, 1, 'Share your child''s progress story with the community');

-- ============================================================================
-- VERIFICATION QUEUE SEED (for admin review)
-- ============================================================================

-- Note: Verification items are created dynamically when parents claim
-- points for actions that require_verification = 1

COMMIT;

-- ============================================================================
-- Update points_refrence table to sync with new rules
-- ============================================================================

-- Map rules to the legacy points_refrence table for backwards compatibility
INSERT INTO points_refrence (admin_id, action_name, points_value, adjust_sign)
SELECT 1, action_name, points_value, '+'
FROM points_earning_rules
ON DUPLICATE KEY UPDATE points_value = VALUES(points_value);

COMMIT;
