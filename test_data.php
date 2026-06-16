<?php
require 'connection.php';
$res1 = $connect->query("SELECT * FROM voice_sample ORDER BY sent_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$res2 = $connect->query("SELECT * FROM speech_analysis ORDER BY analyzed_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r(['voice_sample' => $res1, 'speech_analysis' => $res2]);
