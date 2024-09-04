<?php
include "config.php";


if (empty($postjson["role"])) {
    echo json_encode(array("status" => "error", "message" => "all fields are required"));
    exit;
}

try {


    // echo "SSS".$postjson["role"]["permissions"][0];
    $roleName = $postjson["role"]["name"];
    // echo "ddddd".$roleName;



    $stmt = $pdo->prepare("SELECT * FROM roles WHERE roleName=?");
    $stmt->execute([$roleName]);
    $role = $stmt->fetchColumn();
    if (!empty($role)) {
        echo json_encode(["success" => false, "message" => "roleName is already exist!"]);
        exit;
    }else{
        $stmtUser = $pdo->prepare("INSERT INTO roles (roleName) VALUES (?)");
        if($stmtUser->execute([$roleName])){
            $roleId = $pdo->lastInsertId();
            foreach($postjson["role"]["permissions"] as $permission){
                $stmtPermission=$pdo->prepare("INSERT INTO rolepermissions (roleId, permissionId) VALUES (?,?)");
                $stmtPermission->execute([$roleId, $permission]);
            }
            echo json_encode(["success"=> true, "message"=> "roleName has been inserted!"]);
        }else{
            echo json_encode(["success"=> false, "message"=> "failed to insert the roleName!"]);
        }
    }
} catch (Exception $e) {
    echo json_encode(array("success" => "error", "message" => $e->getMessage()));
}