<?php
require 'connection.php';
$res1 = $connect->query("SHOW COLUMNS FROM voice_sample")->fetchAll(PDO::FETCH_ASSOC);
$res2 = $connect->query("SHOW COLUMNS FROM speech_analysis")->fetchAll(PDO::FETCH_ASSOC);
print_r(['voice_sample' => $res1, 'speech_analysis' => $res2]);
