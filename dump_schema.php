<?php
include 'connection.php';
$tables = $connect->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$schema = "";
foreach($tables as $t) {
    $create = $connect->query("SHOW CREATE TABLE $t")->fetch(PDO::FETCH_ASSOC);
    $schema .= $create['Create Table'] . ";\n\n";
}
file_put_contents('grad_schema.txt', $schema);
echo "Schema written to grad_schema.txt";
?>
