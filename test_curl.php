<?php
$url = "http://localhost/Bright%20Steps%20Website/api/api_get_specialist_slots.php?specialist_id=71&date=2026-06-12";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$headers = [
    'Cookie: PHPSESSID=your_session_id', // Wait, I can't mock the session cookie easily via curl unless I know it
];
$output = curl_exec($ch);
curl_close($ch);
echo "Response:\n";
var_dump($output);
