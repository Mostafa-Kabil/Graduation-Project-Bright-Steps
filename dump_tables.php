<?php
$c = new PDO('mysql:host=localhost;dbname=grad;charset=utf8', 'root', '');
$res = $c->query('SHOW TABLES');
while ($row = $res->fetch(PDO::FETCH_NUM)) {
    echo $row[0] . "\n";
}
