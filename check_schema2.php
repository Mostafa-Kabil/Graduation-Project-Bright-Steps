<?php
include 'connection.php';
print_r($connect->query("DESCRIBE child")->fetchAll(PDO::FETCH_ASSOC));
echo "\n\nSample children:\n";
print_r($connect->query("SELECT child_id, first_name, birth_year, birth_month, birth_day FROM child WHERE child_id IN (15, 17, 208, 14, 18) LIMIT 5")->fetchAll(PDO::FETCH_ASSOC));
echo "\n\nSpecialist users status:\n";
print_r($connect->query("SELECT user_id, email, status FROM users WHERE user_id IN (15, 245, 246)")->fetchAll(PDO::FETCH_ASSOC));
