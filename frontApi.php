<?php

header('Access-Control-Allow-Origin: *');

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


include "config.php";


// $mysqli= new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) or die("error");


mysqli_set_charset($mysqli, 'utf8');

$postjson = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die("Error: " . $e->getMessage());
}

if (isset($postjson["method"])) {

    if ($postjson["method"] == "login") {
        // Validate input fields 
        if (empty($postjson['userEmail']) || empty($postjson['userPassword'])) {
            http_response_code(400); // Bad Request
            echo json_encode(array('success' => false, 'message' => 'All fields are required.'));
            exit;
        }
        // Check if the email, username, and company code combination already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userEmail = ? ");
        $stmt->execute([$postjson['userEmail']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($user)) {
            http_response_code(400); // Bad Request
            $response = array(
                'success' => false,
                'message' => 'this email is not exists.'
            );
        } else {
            // Verify the provided password against the stored hashed password
            // $hashedPassword = password_hash($postjson['userPassword'], PASSWORD_DEFAULT);
            // echo $postjson['userPassword']."//".$user['userPassword']."/////";

            if (password_verify($postjson['userPassword'], $user['userPassword'])) {
                $response = array(
                    'success' => true,
                    'msg' => 'Login successful.',
                    'user' => $user
                );
            } else {
                $response = array(
                    'success' => false,
                    'msg' => 'Incorrect password.'
                );
            }
        }
        echo json_encode($response);
    }

    function generateToken($length = 32)
    {
        $tokenBytes = random_bytes($length);
        return bin2hex($tokenBytes);
    }

    if ($postjson["method"] == "signup") {
        // echo "sss";
        try {
            // Define the data to be inserted
            $userFullname = $postjson["userFullname"];
            $userEmail = $postjson["userEmail"];
            $password = password_hash($postjson['password'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("SELECT * FROM users WHERE userEmail = ?");
            $stmt->execute([$postjson['userEmail']]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);


            if (!empty($users)) {

                // http_response_code(400); // Bad Request
                $result = json_encode(
                    array(
                        'success' => false,
                        'msg' => 'This Email is already exists.'
                    )
                );
            } else {
                $userToken = generateToken(32);
                // Prepare the SQL statement with placeholders
                $stmt = $pdo->prepare("INSERT INTO users (userFullname, userEmail , userPassword,userToken)
                                   VALUES (:userFullname, :userEmail , :password , :userToken)");

                // Bind parameters
                $stmt->bindParam(':userFullname', $userFullname);
                $stmt->bindParam(':userEmail', $userEmail);
                $stmt->bindParam(':password', $password);
                $stmt->bindParam(':userToken', $userToken);

                // Execute the statement
                if ($stmt->execute()) {
                    // send email to user to verify the email
                    // $to = $userEmail;
                    // $subject = 'Verify Your Email';
                    // $message = 'Click the following link to verify your email: http://localhost:4200/#/verify?token=' . $userToken;
                    // $headers = 'From: info@inventorycollector.com' . "\r\n" .
                    //     'Reply-To: info@inventorycollector.com' . "\r\n" .
                    //     'X-Mailer: PHP/' . phpversion();

                    // // Send the email
                    // if (mail($to, $subject, $message, $headers)) {
                    //     // Email sent successfully
                    //     $email = "Email sent";
                    //     // echo 'Email sent. Check your inbox to verify your email.';
                    // } else {
                    //     // Email not sent
                    //     $email = "Email not sent";
                    //     // $lastError = error_get_last();
                    //     // error_log('Email error: ' . print_r($lastError, true));
                    //     // echo 'Email could not be sent. Please try again later.';
                    // }

                    $result = json_encode(array('success' => true));
                } else {
                    $result = json_encode(array('success' => false, "msg" => "error in inserting data"));
                }

            }
            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "view-cats") {
        try {
            // Initialize an array to hold the categories and subcategories
            $categoriesWithSubcategories = array();

            // Prepare an SQL query to retrieve categories and their subcategories
            $stmt = $pdo->prepare("SELECT c.catId, c.catName,c.catNameAr, c.catPhoto, s.subId, s.subName, s.subPhoto
                                   FROM categories AS c
                                   LEFT JOIN subCategory AS s ON c.catId = s.catId");

            // Execute the query
            if ($stmt->execute()) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $categoryId = $row['catId'];
                    $categoryName = $row['catName'];
                    $categoryNameAr = $row['catNameAr'];
                    $categoryPhoto = $row['catPhoto'];
                    $subcategoryId = $row['subId'];
                    $subcategoryName = $row['subName'];
                    $subcategoryPhoto = $row['subPhoto'];

                    // Check if the category is already in the result array
                    if (!isset($categoriesWithSubcategories[$categoryId])) {
                        $categoriesWithSubcategories[$categoryId] = array(
                            'categoryId' => $categoryId,
                            'categoryName' => $categoryName,
                            'categoryNameAr' => $categoryNameAr,
                            'categoryPhoto' => $categoryPhoto,
                            'subcategories' => array()
                        );
                    }

                    // Add the subcategory to the category's subcategories array
                    if ($subcategoryId !== null) {
                        $categoriesWithSubcategories[$categoryId]['subcategories'][] = array(
                            'subcategoryId' => $subcategoryId,
                            'subcategoryName' => $subcategoryName,
                            'subcategoryPhoto' => $subcategoryPhoto
                        );
                    }
                }

                $result = json_encode(array('success' => true, 'data' => array_values($categoriesWithSubcategories)));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }

    }

    if ($postjson["method"] == "item-params") {
        try {
            $userId = $postjson["userId"];
            $itemId = $postjson["itemId"];

            // Fetch user account type
            $stmtUser = $pdo->prepare("SELECT userAccountType FROM users WHERE userId = ?");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            $accountType = $user['userAccountType'];

            // Determine the price column based on account type
            $priceColumn = "regularPrice";
            if ($accountType == "gold") {
                $priceColumn = "goldPrice";
            } elseif ($accountType == "platinum") {
                $priceColumn = "platinumPrice";
            }

            // Check if the user has a custom price for this item
            $stmtCustomPrice = $pdo->prepare("SELECT customPrice FROM custompriceitem WHERE itemId = ? AND userId = ?");
            $stmtCustomPrice->execute([$itemId, $userId]);
            $customPrice = $stmtCustomPrice->fetch(PDO::FETCH_ASSOC);

            $stmtParams = $pdo->prepare("SELECT *
                FROM itemparameters
                WHERE ItemID = ?");

            // Use the appropriate price based on the presence of a custom price
            $priceQuery = $customPrice ? $customPrice['customPrice'] : "i.$priceColumn";

            // Prepare SQL to retrieve item info
            // Note: Use a CASE statement to dynamically select the custom price or the regular price column
            $stmtItemInfo = $pdo->prepare("SELECT i.ItemID, i.Name, i.is_stock_item, i.item_qty_In_stock,
                CASE WHEN cp.customPrice IS NOT NULL THEN cp.customPrice ELSE i.$priceColumn END AS Price, 
                i.Available, i.QuantityMin, i.QuantityMax, i.ProductType, i.itemPhoto
                FROM items i
                LEFT JOIN custompriceitem cp ON cp.itemId = i.ItemID AND cp.userId = ?
                WHERE i.ItemID = ?");

            $stmtUserBalance = $pdo->prepare("SELECT userBalance
                FROM users
                WHERE userId = ?");

            if ($stmtParams->execute([$itemId]) && $stmtItemInfo->execute([$userId, $itemId]) && $stmtUserBalance->execute([$userId])) {
                $params = $stmtParams->fetchAll(PDO::FETCH_ASSOC);
                $itemInfo = $stmtItemInfo->fetchAll(PDO::FETCH_ASSOC);
                $balance = $stmtUserBalance->fetch(PDO::FETCH_ASSOC);

                // Check if the ProductType is specificPackage
                $specificPackages = [];
                if ($itemInfo['ProductType'] === 'specificPackage') {
                    $stmtSpecificPackages = $pdo->prepare("SELECT * FROM SpecificPackages WHERE ItemID = ?");
                    if ($stmtSpecificPackages->execute([$itemId])) {
                        $specificPackages = $stmtSpecificPackages->fetchAll(PDO::FETCH_ASSOC);
                    }
                }

                $result = json_encode(array('success' => true, 'params' => $params, "itemInfo" => $itemInfo, "balance" => $balance["userBalance"], "specificPackages" => $specificPackages));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    // if ($postjson["method"] == "item-params") {
    //     try {

    //         $userId = $postjson["userId"];
    //         $itemId = $postjson["itemId"];


    //         $stmtUser = $pdo->prepare("SELECT userAccountType FROM users WHERE userId = ?");
    //         $stmtUser->execute([$postjson["userId"]]);
    //         $user = $stmtUser->fetch(PDO::FETCH_ASSOC);


    //         $accountType = $user['userAccountType'];

    //         // Determine the price column based on account type
    //         $priceColumn = "regularPrice";
    //         if ($accountType == "gold") {
    //             $priceColumn = "goldPrice";
    //         } elseif ($accountType == "platinum") {
    //             $priceColumn = "platinumPrice";
    //         }

    //         // Check if the user has a custom price for this item
    //         $stmtCustomPrice = $pdo->prepare("SELECT customPrice FROM custompriceitem WHERE itemId = ? AND userId = ?");
    //         $stmtCustomPrice->execute([$itemId, $userId]);
    //         $customPrice = $stmtCustomPrice->fetch(PDO::FETCH_ASSOC);


    //         // Prepare an SQL query to retrieve categories and their subcategories
    //         $stmtParams = $pdo->prepare("SELECT *
    //             FROM itemparameters
    //             WHERE ItemID =?");

    //         $stmtItemInfo = $pdo->prepare("SELECT i.ItemID, i.Name, i.$priceColumn AS Price, i.Available, i.QuantityMin, i.QuantityMax, i.ProductType, i.itemPhoto
    //         FROM items i
    //         WHERE i.ItemID =?
    //         ");

    //         $stmtUserBalance = $pdo->prepare("SELECT userBalance
    //         FROM users
    //         WHERE userId =?");

    //         if ($stmtParams->execute([$postjson["itemId"]]) && $stmtItemInfo->execute([$postjson["itemId"]]) && $stmtUserBalance->execute([$postjson["userId"]])) {
    //             $params = $stmtParams->fetchAll(PDO::FETCH_ASSOC);
    //             $itemInfo = $stmtItemInfo->fetchAll(PDO::FETCH_ASSOC);
    //             $balance = $stmtUserBalance->fetch(PDO::FETCH_ASSOC);
    //             $result = json_encode(array('success' => true, 'params' => $params, "itemInfo" => $itemInfo, "balance" => $balance["userBalance"]));
    //         } else {
    //             $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
    //         }




    //         echo $result;
    //     } catch (PDOException $e) {
    //         echo json_encode(array('success' => false, 'error' => $e->getMessage()));
    //     }
    // }

    if ($postjson["method"] == "check-mode") {
        try {

            // Prepare an SQL query to retrieve categories and their subcategories
            $stmt = $pdo->prepare("SELECT * FROM settings WHERE key_name='under_construction'");


            if ($stmt->execute()) {

                $underConstructionMode = $stmt->fetchColumn(PDO::FETCH_ASSOC);
                $result = json_encode(array('success' => true, "underConstructionMode" => $underConstructionMode));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }




            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "view-cats-by-group-id") {
        try {

            // Prepare an SQL query to retrieve categories and their subcategories
            $stmt = $pdo->prepare("SELECT *
            FROM categories c
            WHERE c.groupId = ? 
            AND EXISTS (
                SELECT 1 
                FROM items i 
                WHERE i.categoryId = c.CategoryID AND i.is_active = 1
            )");


            if ($stmt->execute([$postjson["groupId"]])) {

                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result = json_encode(array('success' => true, "categories" => $categories));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }




            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    if ($postjson["method"] == "view-cats-items") {
        try {

            // Prepare an SQL query to retrieve categories and their subcategories
            $stmt = $pdo->prepare("SELECT CategoryID, CategoryName, catPhoto
                FROM categories
                WHERE CategoryID != 0");

            $stmtItems = $pdo->prepare("SELECT i.ItemID, i.Name, i.Price, i.Available, i.QuantityMin, i.QuantityMax, i.ProductType, i.itemPhoto
            FROM items i
            WHERE i.ParentID = 0
            ORDER BY CategoryID;");

            $stmtGroups = $pdo->prepare("SELECT * FROM groups");

            if ($stmt->execute() && $stmtItems->execute() && $stmtGroups->execute()) {
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                $groups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);
                $result = json_encode(array('success' => true, 'categories' => $categories, "items" => $items, "groups" => $groups));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }




            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }
    if ($postjson["method"] == "user-balance") {
        try {



            $stmtUserBalance = $pdo->prepare("SELECT userBalance, userAccountType
                FROM users
                WHERE userId =?");

            if ($stmtUserBalance->execute([$postjson["userId"]])) {

                $balance = $stmtUserBalance->fetch(PDO::FETCH_ASSOC);
                $result = json_encode(array('success' => true, 'userBalance' => $balance));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }




            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "view-items") {
        try {
            // Fetch the user's account type
            $stmtUser = $pdo->prepare("SELECT userAccountType FROM users WHERE userId = ?");
            $stmtUser->execute([$postjson["userId"]]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $accountType = $user['userAccountType'];

                // Determine the price column based on account type
                $priceColumn = "regularPrice";
                if ($accountType == "gold") {
                    $priceColumn = "goldPrice";
                } elseif ($accountType == "platinum") {
                    $priceColumn = "platinumPrice";
                }

                // Prepare an SQL query to retrieve items with the appropriate price
                // $stmt = $pdo->prepare("SELECT i.*, c.catPhoto, c.CategoryName, i.$priceColumn AS itemPrice 
                //                        FROM items i
                //                        LEFT JOIN categories c ON c.CategoryID = i.ParentID 
                //                        WHERE i.ParentID = ?");

                $stmt = $pdo->prepare("
                SELECT i.*, c.catPhoto, c.CategoryName, 
                       COALESCE(cp.customPrice, i.$priceColumn) AS itemPrice
                FROM items i
                LEFT JOIN categories c ON c.CategoryID = i.ParentID
                LEFT JOIN custompriceitem cp ON cp.itemId = i.ItemID AND cp.userId = ?
                WHERE i.ParentID = ?
            ");

                // if ($stmt->execute([$postjson["catId"]])) {
                if ($stmt->execute([$postjson["userId"], $postjson["catId"]])) {
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $result = json_encode(array('success' => true, 'data' => $items));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
                }
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'User not found'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    if ($postjson["method"] == "view-sub-cat") {
        try {


            // Prepare an SQL query to retrieve categories and their subcategories
            $stmt = $pdo->prepare("SELECT * from subcategory WHERE catId=?");
            if ($stmt->execute([$postjson["catId"]])) {
                $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result = json_encode(array('success' => true, 'data' => $subCategories));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }




            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }


    }


    if ($postjson["method"] == "view-marquees") {
        $stmt = $pdo->prepare("SELECT * FROM marquees");
        $stmt->execute();
        $marquees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($marquees)) {
            http_response_code(400); // Bad Request
            $response = array(
                'success' => false,
                'message' => 'there is no payments.'
            );
        } else {

            $response = array(
                'success' => true,
                'message' => 'info retrived successful.',
                'marquees' => $marquees
            );

        }
        echo json_encode($response);
    }

    if ($postjson["method"] == "view-orders") {
        try {
            // Fetch the user's account type
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE userId = ?");
            $stmtUser->execute([$postjson["userId"]]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user) {


                $stmt = $pdo->prepare("SELECT o.*, i.Name 
                                       FROM orders o
                                       LEFT JOIN items i ON i.ItemID = o.orderItemId 
                                       WHERE o.orderUserId = ?");

                if ($stmt->execute([$postjson["userId"]])) {
                    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $result = json_encode(array('success' => true, 'orders' => $orders));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
                }
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'User not found'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "order-details") {
        try {
            // Fetch the user's account type
            $stmtOrder = $pdo->prepare("SELECT o.*, i.Name FROM orders o
            LEFT JOIN items i ON i.ItemID = o.orderItemId
            WHERE orderId = ?");
            $stmtOrder->execute([$postjson["orderId"]]);
            $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                $stmtOrderCards = $pdo->prepare("SELECT * FROM card_details WHERE item_id IN (SELECT orderItemId from orders WHERE orderId = ?) AND orderId = ?");
                if ($stmtOrderCards->execute([$postjson["orderId"], $postjson["orderId"]])) {
                    $orderCards = $stmtOrderCards->fetchAll(PDO::FETCH_ASSOC);
                }
                $result = json_encode(array('success' => true, 'order' => $order, "cards" => $orderCards));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'order not found'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    function generateUuidV4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff), // 32 bits for "time_low"
            mt_rand(0, 0xffff), // 16 bits for "time_mid"
            mt_rand(0, 0x0fff) | 0x4000, // 16 bits for "time_hi_and_version", version 4
            mt_rand(0, 0x3fff) | 0x8000, // 16 bits, 8 bits for "clk_seq_hi_res", 8 bits for "clk_seq_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff) // 48 bits for "node"
        );
    }



    if ($postjson["method"] == "new-order") {

        try {
            // userId,itemId,qty,amount,total,paramValue
            $uuid = generateUuidV4();
            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("INSERT INTO orders (orderUserId, orderItemId , orderPlayerId, order_uuid, orderQty, orderTotal)
                                   VALUES (:orderUserId, :orderItemId , :orderPlayerId, :order_uuid, :orderQty, :orderTotal)");

            // Bind parameters
            $stmt->bindParam(':orderUserId', $postjson["userId"]);
            $stmt->bindParam(':orderItemId', $postjson["itemId"]);
            $stmt->bindParam(':orderPlayerId', $postjson["paramValue"]);
            $stmt->bindParam(':order_uuid', $uuid);
            $stmt->bindParam(':orderQty', $postjson["qty"]);
            $stmt->bindParam(':orderTotal', $postjson["total"]);


            // Execute the statement
            if ($stmt->execute()) {
                // Retrieve the last inserted order ID
                $orderId = $pdo->lastInsertId();
                $updateBalanceStmt = $pdo->prepare("UPDATE users SET userBalance = userBalance - :total WHERE userId = :userId");
                $updateBalanceStmt->bindParam(':total', $postjson["total"]);
                $updateBalanceStmt->bindParam(':userId', $postjson["userId"]);
                if ($updateBalanceStmt->execute()) {
                    // Fetch autoProceed value for the item
                    $autoProceedStmt = $pdo->prepare("SELECT autoProceed FROM items WHERE ItemID = :itemId");
                    $autoProceedStmt->bindParam(':itemId', $postjson["itemId"]);
                    $autoProceedStmt->execute();
                    $autoProceed = $autoProceedStmt->fetchColumn();

                    if ($autoProceed == 1) {
                        // Call the external API if autoProceed is 1
                        $apiUrl = "https://api.fastycard.com/client/api/newOrder/" . $postjson["itemId"] . "/params?qty=" . $postjson["qty"] . "&playerId=" . $postjson["paramValue"] . "&order_uuid=" . $uuid;
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $apiUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt(
                            $ch,
                            CURLOPT_HTTPHEADER,
                            array(
                                'api-token: b091e0e21a9d098d10a9cb95d2a2aaa4e9c1b67efb9136d7',
                                'Content-Type: application/json'
                            )
                        );

                        $response = curl_exec($ch);
                        if (curl_errno($ch)) {
                            echo json_encode(array('success' => false, 'msg' => 'Failed to call external API: ' . curl_error($ch)));
                        } else {
                            $responseData = json_decode($response, true);

                            // Check if the data key exists and is an array
                            if (isset($responseData['data'])) {
                                $orderIdFromApi = $responseData['data']['order_id'] ?? null;
                                $statusFromApi = $responseData['data']['status'] ?? null;

                                // Update your orders table with these values
                                $responseUpdateStmt = $pdo->prepare("
                                                                        UPDATE orders 
                                                                        SET response = :response, orderIdFromApi = :orderIdFromApi, statusFromApi = :statusFromApi
                                                                        WHERE order_uuid = :order_uuid
                                                                    ");
                                $responseUpdateStmt->bindParam(':response', $response);
                                $responseUpdateStmt->bindParam(':orderIdFromApi', $orderIdFromApi);
                                $responseUpdateStmt->bindParam(':statusFromApi', $statusFromApi);
                                $responseUpdateStmt->bindParam(':order_uuid', $uuid);
                                $responseUpdateStmt->execute();
                            }

                            echo json_encode(['success' => true, 'status' => 'OK', 'response' => $responseData, "orderId" => $orderId]); // Sending back the API response to frontend
                        }
                        curl_close($ch);
                    } else {
                        // AutoProceed is 0, only store in database
                        $cardsDetailsStmt = $pdo->prepare("SELECT is_stock_item, item_qty_In_stock FROM items WHERE ItemID = :itemId");
                        $cardsDetailsStmt->bindParam(':itemId', $postjson["itemId"]);
                        $cardsDetailsStmt->execute();
                        $cardsDetails = $cardsDetailsStmt->fetch();
                        if ($cardsDetails["is_stock_item"] == 1) {
                            // reduce  cards qty from stock
                            $itemQty = $cardsDetails["item_qty_In_stock"] - $postjson["qty"];
                            $updateStmt = $pdo->prepare("UPDATE items SET item_qty_In_stock = :newQty WHERE ItemID = :itemId");
                            $updateStmt->bindParam(':newQty', $itemQty);
                            $updateStmt->bindParam(':itemId', $postjson["itemId"]);
                            $updateStmt->execute();

                            // assign userId & orderId to cardDetails
                            $updateCardDetailsStmt = $pdo->prepare("
                                UPDATE card_details 
                                SET takenBy_user_id = :userId, orderId = :orderId 
                                WHERE item_id = :itemId 
                                AND takenBy_user_id IS NULL 
                                LIMIT :qty
                            ");
                            $updateCardDetailsStmt->bindParam(':userId', $postjson["userId"]);
                            $updateCardDetailsStmt->bindParam(':orderId', $orderId);
                            $updateCardDetailsStmt->bindParam(':itemId', $postjson["itemId"]);
                            $updateCardDetailsStmt->bindParam(':qty', $postjson["qty"], PDO::PARAM_INT);
                            $updateCardDetailsStmt->execute();
                        }

                        echo json_encode(array('success' => true, 'msg' => 'Order stored in database without external processing', "orderId" => $orderId));
                    }
                } else {
                    echo json_encode(array('success' => false, 'msg' => 'Error updating balance'));
                }

            } else {
                echo json_encode(array('success' => false, "msg" => "error in inserting data"));
            }



        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

}


if (isset($_POST["method"])) {

    if ($_POST["method"] == "recharge-account") {
        try {

            $userId = $_POST["userId"];
            $value = $_POST["value"];
            $type = $_POST["type"];

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];
                $fileType = mime_content_type($fileTmpName);

                // Allowed MIME types for images
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK && in_array($fileType, $allowedMimeTypes)) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . 'payment.' . $extension; // Append unique ID to the file name

                    $uploadDirectory = 'uploads/' . $newFileName;

                    // Move the uploaded file to the specified directory
                    if (move_uploaded_file($fileTmpName, $uploadDirectory)) {
                        // The file was successfully uploaded
                        $fileName = $newFileName; // Update the file name to the new unique name
                    } else {
                        // Handle the case where file upload failed
                        $result = json_encode(array('success' => false, 'msg' => 'Error moving uploaded file'));
                        echo $result;
                        exit;
                    }
                } else {
                    // Handle the case where file upload had an error or the file is not an allowed image type
                    $msg = $fileError !== UPLOAD_ERR_OK ? 'File upload error: ' . $fileError : 'Invalid file type. Only images are allowed.';
                    $result = json_encode(array('success' => false, 'msg' => $msg));
                    echo $result;
                    exit;
                }
            }



            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("INSERT INTO payments ( paymentUserId, paymentReciept, paymentType, paymentValue) VALUES (:paymentUserId, :paymentReciept, :paymentType, :paymentValue)");

            // Bind parameters
            $stmt->bindParam(':paymentUserId', $userId);
            $stmt->bindParam(':paymentValue', $value);
            $stmt->bindParam(':paymentType', $type);

            $stmt->bindParam(':paymentReciept', $fileName); // Store the file name in the database

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true, 'msg' => 'image has been uploaded!'));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error inserting item'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


}