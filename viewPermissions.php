<?php
include "config.php";

$stmt = $pdo->prepare("SELECT * FROM permissions");
$stmt->execute();
$permissions=$stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(["permissions"=> $permissions,"success"=>true]);