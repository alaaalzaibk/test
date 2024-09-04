<?php
include "config.php";


if (empty($postjson["userName"]) || empty($postjson["userPassword"]) || empty($postjson["roleId"])) {
    echo json_encode(array("status" => "error", "message" => "all fields are required"));
    exit;
}

try {

    // echo "SSS".$postjson["userName"];
    $userName = $postjson["userName"];
    $userPassword = password_hash($postjson["userPassword"], PASSWORD_DEFAULT);
    $roleId = $postjson["roleId"];


    $stmt = $pdo->prepare("SELECT * FROM usrs WHERE userName=?");
    $stmt->execute([$userName]);
    $user = $stmt->fetchColumn();
    if (!empty($user)) {
        echo json_encode(["status" => false, "msg" => "user is already exist!"]);
        exit;
    }else{
        $stmtUser = $pdo->prepare("INSERT INTO usrs (userName,userPassword,userRoleId) VALUES (?,?,?)");
        if($stmtUser->execute([$userName, $userPassword, $roleId])){
            echo json_encode(["status"=> true, "message"=> "user has been inserted!"]);
        }else{
            echo json_encode(["status"=> false, "message"=> "failed to insert the user!"]);
        }
    }
} catch (Exception $e) {
    echo json_encode(array("status" => "error", "message" => $e->getMessage()));
}