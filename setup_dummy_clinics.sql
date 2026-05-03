-- Insert Dummy Clinics
INSERT INTO `clinic` (`admin_id`, `clinic_name`, `email`, `password`, `location`, `status`, `rating`) VALUES
(1, 'Bright Steps Central', 'contact@brightsteps.com', 'password123', 'New York, NY', 'active', 4.90),
(1, 'Pediatric Care Experts', 'info@pediatriccx.com', 'password123', 'Los Angeles, CA', 'active', 4.80),
(1, 'Sunny Smiles Speech Therapy', 'hello@sunnysmiles.com', 'password123', 'Chicago, IL', 'active', 4.75),
(1, 'Thrive Child Development', 'support@thrivedevelopment.org', 'password123', 'Online', 'active', 4.95);

-- Insert Users for Specialists
INSERT IGNORE INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `role`, `status`, `phone`) VALUES
(40, 'Sarah', 'Jenkins', 's.jenkins@brightsteps.com', 'pass', 'doctor', 'active', '555-0101'),
(41, 'Michael', 'Stone', 'm.stone@brightsteps.com', 'pass', 'doctor', 'active', '555-0102'),
(42, 'Alicia', 'Gomez', 'a.gomez@pediatric.com', 'pass', 'doctor', 'active', '555-0103'),
(43, 'Robert', 'Cheng', 'r.cheng@pediatric.com', 'pass', 'doctor', 'active', '555-0104'),
(44, 'Emily', 'Davis', 'e.davis@sunny.com', 'pass', 'doctor', 'active', '555-0105'),
(45, 'James', 'Wilson', 'j.wilson@sunny.com', 'pass', 'doctor', 'active', '555-0106'),
(46, 'Olivia', 'Martinez', 'o.martinez@thrive.com', 'pass', 'doctor', 'active', '555-0107'),
(47, 'William', 'Taylor', 'w.taylor@thrive.com', 'pass', 'doctor', 'active', '555-0108');

-- Insert Dummy Specialists using subqueries to get correct clinic IDs
INSERT IGNORE INTO `specialist` (`specialist_id`, `clinic_id`, `first_name`, `last_name`, `specialization`, `experience_years`)
SELECT 40, clinic_id, 'Sarah', 'Jenkins', 'Speech-Language Pathologist', 8 FROM clinic WHERE clinic_name = 'Bright Steps Central' LIMIT 1;
INSERT IGNORE INTO `specialist` (`specialist_id`, `clinic_id`, `first_name`, `last_name`, `specialization`, `experience_years`)
SELECT 41, clinic_id, 'Michael', 'Stone', 'Occupational Therapist', 12 FROM clinic WHERE clinic_name = 'Bright Steps Central' LIMIT 1;
INSERT IGNORE INTO `specialist` (`specialist_id`, `clinic_id`, `first_name`, `last_name`, `specialization`, `experience_years`)
SELECT 42, clinic_id, 'Alicia', 'Gomez', 'Behavioral Therapist', 5 FROM clinic WHERE clinic_name = 'Pediatric Care Experts' LIMIT 1;
INSERT IGNORE INTO `specialist` (`specialist_id`, `clinic_id`, `first_name`, `last_name`, `specialization`, `experience_years`)
SELECT 43, clinic_id, 'Robert', 'Cheng', 'Pediatrician', 15 FROM clinic WHERE clinic_name = 'Pediatric Care Experts' LIMIT 1;
INSERT IGNORE INTO `specialist` (`specialist_id`, `clinic_id`, `first_name`, `last_name`, `specialization`, `experience_years`)
SELECT 44, clinic_id, 'Emily', 'Davis', 'Speech-Language Pathologist', 4 FROM clinic WHERE clinic_name = 'Sunny Smiles Speech Therapy' LIMIT 1;
INSERT IGNORE INTO `specialist` (`specialist_id`, `clinic_id`, `first_name`, `last_name`, `specialization`, `experience_years`)
SELECT 45, clinic_id, 'James', 'Wilson', 'Child Psychologist', 10 FROM clinic WHERE clinic_name = 'Sunny Smiles Speech Therapy' LIMIT 1;
INSERT IGNORE INTO `specialist` (`specialist_id`, `clinic_id`, `first_name`, `last_name`, `specialization`, `experience_years`)
SELECT 46, clinic_id, 'Olivia', 'Martinez', 'Behavioral Therapist', 7 FROM clinic WHERE clinic_name = 'Thrive Child Development' LIMIT 1;
INSERT IGNORE INTO `specialist` (`specialist_id`, `clinic_id`, `first_name`, `last_name`, `specialization`, `experience_years`)
SELECT 47, clinic_id, 'William', 'Taylor', 'Occupational Therapist', 9 FROM clinic WHERE clinic_name = 'Thrive Child Development' LIMIT 1;
