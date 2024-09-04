<?php
include "config.php";

$stmt = $pdo->prepare("SELECT * FROM roles");
$stmt->execute();
$roles=$stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(["roles"=> $roles,"success"=>true]);