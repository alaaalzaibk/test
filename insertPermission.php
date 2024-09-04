<?php
include "config.php";


if (empty($postjson["permissionName"])) {
    echo json_encode(array("status" => "error", "message" => "all fields are required"));
    exit;
}

try {

    // echo "SSS".$postjson["userName"];
    $permissionName = $postjson["permissionName"];



    $stmt = $pdo->prepare("SELECT * FROM permissions WHERE permissionName=?");
    $stmt->execute([$permissionName]);
    $permission = $stmt->fetchColumn();
    if (!empty($permission)) {
        echo json_encode(["success" => false, "message" => "permission is already exist!"]);
        exit;
    }else{
        $stmtUser = $pdo->prepare("INSERT INTO permissions (permissionName) VALUES (?)");
        if($stmtUser->execute([$permissionName])){
            echo json_encode(["success"=> true, "message"=> "permissionName has been inserted!"]);
        }else{
            echo json_encode(["success"=> false, "message"=> "failed to insert the permissionName!"]);
        }
    }
} catch (Exception $e) {
    echo json_encode(array("success" => "error", "message" => $e->getMessage()));
}