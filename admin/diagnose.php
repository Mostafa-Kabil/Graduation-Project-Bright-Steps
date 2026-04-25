<?php
$db=new PDO('mysql:host=localhost;dbname=grad','root',''); 
$tables = ['child','specialist','appointment','support_tickets'];
foreach($tables as $t) {
  $s=$db->query("DESCRIBE $t");
  echo "Table $t:\n";
  print_r($s->fetchAll(PDO::FETCH_ASSOC));
}
?>
