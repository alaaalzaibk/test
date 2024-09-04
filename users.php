<?php
include "config.php";

$stmt = $pdo->prepare("SELECT * FROM usrs");
$stmt->execute();
$users=$stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(["users"=> $users,"success"=>true]);