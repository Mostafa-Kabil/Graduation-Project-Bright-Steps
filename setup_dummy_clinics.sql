-- Insert Dummy Clinics
INSERT INTO `clinic` (`admin_id`, `clinic_name`, `email`, `password`, `location`, `status`, `rating`) VALUES
(1, 'Bright Steps Central', 'contact@brightsteps.com', 'password123', 'New York, NY', 'active', 4.90),
(1, 'Pediatric Care Experts', 'info@pediatriccx.com', 'password123', 'Los Angeles, CA', 'active', 4.80),
(1, 'Sunny Smiles Speech Therapy', 'hello@sunnysmiles.com', 'password123', 'Chicago, IL', 'active', 4.75),
(1, 'Thrive Child Development', 'support@thrivedevelopment.org', 'password123', 'Online', 'active', 4.95);

-- Insert Users for Specialists
INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `role`, `status`) VALUES
(40, 'Sarah', 'Jenkins', 's.jenkins@brightsteps.com', 'pass', 'doctor', 'active'),
(41, 'Michael', 'Stone', 'm.stone@brightsteps.com', 'pass', 'doctor', 'active'),
(42, 'Alicia', 'Gomez', 'a.gomez@pediatric.com', 'pass', 'doctor', 'active'),
(43, 'Robert', 'Cheng', 'r.cheng@pediatric.com', 'pass', 'doctor', 'active'),
(44, 'Emily', 'Davis', 'e.davis@sunny.com', 'pass', 'doctor', 'active'),
(45, 'James', 'Wilson', 'j.wilson@sunny.com', 'pass', 'doctor', 'active'),
(46, 'Olivia', 'Martinez', 'o.martinez@thrive.com', 'pass', 'doctor', 'active'),
(47, 'William', 'Taylor', 'w.taylor@thrive.com', 'pass', 'doctor', 'active');

-- Insert Dummy Specialists
INSERT INTO `specialist` (`specialist_id`, `clinic_id`, `first_name`, `last_name`, `specialization`, `experience_years`) VALUES
(40, 9, 'Sarah', 'Jenkins', 'Speech-Language Pathologist', 8),
(41, 9, 'Michael', 'Stone', 'Occupational Therapist', 12),
(42, 10, 'Alicia', 'Gomez', 'Behavioral Therapist', 5),
(43, 10, 'Robert', 'Cheng', 'Pediatrician', 15),
(44, 11, 'Emily', 'Davis', 'Speech-Language Pathologist', 4),
(45, 11, 'James', 'Wilson', 'Child Psychologist', 10),
(46, 12, 'Olivia', 'Martinez', 'Behavioral Therapist', 7),
(47, 12, 'William', 'Taylor', 'Occupational Therapist', 9);
