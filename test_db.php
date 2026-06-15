<?php
require 'connection.php';
$stmt = $connect->prepare("
                        SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone,
                               s.specialization, s.experience_years, s.certificate_of_experience, s.clinic_id,
                               COALESCE(c.clinic_name, '') AS clinic_name,
                               COALESCE(c.location, '') AS clinic_location,
                               COALESCE(s.bio, '') AS bio, o.consultation_types, o.focus_areas,
                               o.session_duration, o.max_patients_per_day, o.follow_up_reminder
                        FROM users u
                        LEFT JOIN specialist s ON u.user_id = s.specialist_id
                        LEFT JOIN clinic c ON s.clinic_id = c.clinic_id
                        LEFT JOIN doctor_onboarding o ON u.user_id = o.doctor_id
                        WHERE u.email = 'mostafakabils@gmail.com'
                    ");
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($profile);
?>
