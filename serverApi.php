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

    if ($postjson["method"] == "signup") {
        // Validate input fields 
        if (empty($postjson['userPassword']) || empty($postjson['userMobile'])) {

            http_response_code(400); // Bad Request
            echo json_encode(array('success' => false, 'message' => 'All fields are required.'));
            exit;
        }


        // Check if the email, username combination already exists
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE userMobile = ?");
            $stmt->execute([$postjson['userEmail'], $postjson['userMobile']]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            echo "Database Error: " . $e->getMessage();
        }
        if (!empty($users)) {


            http_response_code(400); // Bad Request
            $response = array(
                'success' => false,
                'message' => 'User with the same phone number already exists.'
            );
        } else {

            // Hash the password
            $hashedPassword = password_hash($postjson['userPassword'], PASSWORD_DEFAULT);

            // Insert user data into the database
            // $stmt = $pdo->prepare("INSERT INTO users (userFirstName, userLastName, userPassword, userEmail,userMobile, userType, userStatus)
            // VALUES (?, ?, ?, ?, ?, 'customer', 0)");
            $stmt = $pdo->prepare("INSERT INTO users (userRole, userPassword, userMobile,  userFirstName, userLastName, userPic, userToken, userAddress)
VALUES ('admin', ?, ?, ?, ?, NULL, NULL, NULL)");

            if ($stmt->execute([$hashedPassword, $postjson['userMobile'], $postjson['userFirstName'], $postjson['userLastName']])) {
                $response = array(
                    'success' => true,
                    'message' => 'User created successfully.'
                );
            } else {


                $response = array(
                    'success' => false,
                    'message' => 'User not created. Please contact support.'
                );
            }

        }

        echo json_encode($response);

    }

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Sawa5card api
    // if ($postjson["method"] == "sync-data") {
    //     $response = array('success' => true, 'data' => array());

    //     try {
    //         // Ensure a category with CategoryID = 0 exists
    //         $stmtZeroCategory = $pdo->prepare("INSERT IGNORE INTO categories (CategoryID, CategoryName) VALUES (0, 'Items Without Category')");
    //         $stmtZeroCategory->execute();

    //         foreach ($postjson['items'] as $item) {
    //             $id = $item['id'];
    //             $name = $item['name'];
    //             $price = $item['price'];
    //             $available = $item['available'];
    //             $category_id = $item['parent_id'];
    //             $product_type = $item['product_type'];

    //             // Calculate the prices
    //             $regularPrice = $price * 1.10;
    //             $goldPrice = $price * 1.075;
    //             $platinumPrice = $price * 1.05;

    //             // Insert category if not exists and category_name is set
    //             if (isset($item['category_name']) && $category_id != 0) {
    //                 $category_name = $item['category_name'];
    //                 $stmtCategories = $pdo->prepare("INSERT INTO categories (CategoryID, CategoryName, fromApi) VALUES (:CategoryID, :CategoryName, 1) ON DUPLICATE KEY UPDATE CategoryName = VALUES(CategoryName)");
    //                 $stmtCategories->bindParam(':CategoryID', $category_id);
    //                 $stmtCategories->bindParam(':CategoryName', $category_name);
    //                 $stmtCategories->execute();
    //             }

    //             // Check if qty_values is set
    //             $qty_min = isset($item['qty_values']['min']) ? $item['qty_values']['min'] : null;
    //             $qty_max = isset($item['qty_values']['max']) ? $item['qty_values']['max'] : null;

    //             // Insert or update item
    //             $sql = "INSERT INTO items (ItemID, Name, Price, RegularPrice, GoldPrice, PlatinumPrice, CategoryID, Available, QuantityMin, QuantityMax, ProductType, ParentID, fromApi)
    //          VALUES (:id, :name, :price, :regularPrice, :goldPrice, :platinumPrice, :category_id, :available, :qty_min, :qty_max, :product_type, :parent_id, 1)
    //          ON DUPLICATE KEY UPDATE Name = VALUES(Name), Price = VALUES(Price), RegularPrice = VALUES(RegularPrice), GoldPrice = VALUES(GoldPrice), PlatinumPrice = VALUES(PlatinumPrice), CategoryID = VALUES(CategoryID), Available = VALUES(Available), QuantityMin = VALUES(QuantityMin), QuantityMax = VALUES(QuantityMax), ProductType = VALUES(ProductType), ParentID = VALUES(ParentID)";
    //             $stmt = $pdo->prepare($sql);
    //             $stmt->bindParam(':id', $id);
    //             $stmt->bindParam(':name', $name);
    //             $stmt->bindParam(':price', $price);
    //             $stmt->bindParam(':regularPrice', $regularPrice);
    //             $stmt->bindParam(':goldPrice', $goldPrice);
    //             $stmt->bindParam(':platinumPrice', $platinumPrice);
    //             $stmt->bindParam(':category_id', $category_id);
    //             $stmt->bindParam(':available', $available);
    //             $stmt->bindParam(':qty_min', $qty_min);
    //             $stmt->bindParam(':qty_max', $qty_max);
    //             $stmt->bindParam(':product_type', $product_type);
    //             $stmt->bindParam(':parent_id', $category_id);
    //             $stmt->execute();



    //             // Insert or update item parameters
    //             foreach ($item['params'] as $param) {
    //                 $checkSql = "SELECT COUNT(*) FROM ItemParameters WHERE ItemID = :ItemID AND ParameterName = :ParameterName";
    //                 $stmtCheck = $pdo->prepare($checkSql);
    //                 $stmtCheck->bindParam(':ItemID', $id);
    //                 $stmtCheck->bindParam(':ParameterName', $param);
    //                 $stmtCheck->execute();
    //                 $count = $stmtCheck->fetchColumn();

    //                 if ($count == 0) {
    //                     $paramSql = "INSERT INTO ItemParameters (ItemID, ParameterName) VALUES (:ItemID, :ParameterName)";
    //                     $stmtParam = $pdo->prepare($paramSql);
    //                     $stmtParam->bindParam(':ItemID', $id);
    //                     $stmtParam->bindParam(':ParameterName', $param);
    //                     $stmtParam->execute();
    //                 }
    //             }

    //             $response['data'][] = array('id' => $id, 'status' => 'synced');
    //         }

    //         echo json_encode($response);

    //     } catch (PDOException $e) {
    //         echo json_encode(array('success' => false, 'error' => $e->getMessage()));
    //     } catch (Exception $e) {
    //         echo json_encode(array('success' => false, 'error' => $e->getMessage()));
    //     }

    //     exit(); // Ensure no further output
    // }

    // fastcard api
    if ($postjson["method"] == "sync-data") {
        $response = array('success' => true, 'data' => array());

        try {
            // Ensure a category with CategoryID = 0 exists
            $stmtZeroCategory = $pdo->prepare("INSERT IGNORE INTO categories (CategoryID, CategoryName) VALUES (0, 'Items Without Category')");
            $stmtZeroCategory->execute();

            // Array to keep track of ItemIDs from the external API
            $apiItemIds = [];

            foreach ($postjson['items'] as $item) {
                $id = $item['id'];
                $name = $item['name'];
                $price = $item['price'];
                $available = $item['available'];
                $category_id = $item['parent_id'];
                $product_type = $item['product_type'];

                // Add the ItemID to the array
                $apiItemIds[] = $id;

                // Calculate the prices
                $regularPrice = $price * 1.10;
                $goldPrice = $price * 1.075;
                $platinumPrice = $price * 1.05;

                // Insert category if not exists and category_name is set
                if (isset($item['category_name']) && $category_id != 0) {
                    $category_name = $item['category_name'];
                    $stmtCategories = $pdo->prepare("INSERT INTO categories (CategoryID, CategoryName, fromApi) VALUES (:CategoryID, :CategoryName, 1) ON DUPLICATE KEY UPDATE CategoryName = VALUES(CategoryName)");
                    $stmtCategories->bindParam(':CategoryID', $category_id);
                    $stmtCategories->bindParam(':CategoryName', $category_name);
                    $stmtCategories->execute();
                }

                // Check if qty_values is set
                $qty_min = isset($item['qty_values']['min']) ? $item['qty_values']['min'] : null;
                $qty_max = isset($item['qty_values']['max']) ? $item['qty_values']['max'] : null;

                // Check if item already exists
                // $stmtCheck = $pdo->prepare("SELECT * FROM items WHERE ItemID = :id AND Name = :name");
                // $stmtCheck->bindParam(':id', $id);
                // $stmtCheck->bindParam(':name', $name);
                // $stmtCheck->execute();
                // $existingItem = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                // Check if item already exists
                $stmtCheck = $pdo->prepare("SELECT * FROM items WHERE ItemID = :id");
                $stmtCheck->bindParam(':id', $id);
                $stmtCheck->execute();
                $existingItem = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                // $itemExists = $stmtCheck->fetchColumn();

                if ($existingItem) {

                    // Update only if other fields have changed
                    // If item exists and is_active = 0, activate and update it
                    if ($existingItem['is_active'] == 0) {
                        $updateSql = "UPDATE items SET 
                                            is_active = 1, Name = :name, Price = :price, regularPrice = :regularPrice, goldPrice = :goldPrice, 
                                            platinumPrice = :platinumPrice, CategoryID = :category_id, Available = :available, 
                                            QuantityMin = :qty_min, QuantityMax = :qty_max, ProductType = :product_type, ParentID = :parent_id 
                                            WHERE ItemID = :id";
                        $stmtUpdate = $pdo->prepare($updateSql);
                        $stmtUpdate->bindParam(':id', $id);
                        $stmtUpdate->bindParam(':name', $name);
                        $stmtUpdate->bindParam(':price', $price);
                        $stmtUpdate->bindParam(':regularPrice', $regularPrice);
                        $stmtUpdate->bindParam(':goldPrice', $goldPrice);
                        $stmtUpdate->bindParam(':platinumPrice', $platinumPrice);
                        $stmtUpdate->bindParam(':category_id', $category_id);
                        $stmtUpdate->bindParam(':available', $available);
                        $stmtUpdate->bindParam(':qty_min', $qty_min);
                        $stmtUpdate->bindParam(':qty_max', $qty_max);
                        $stmtUpdate->bindParam(':product_type', $product_type);
                        $stmtUpdate->bindParam(':parent_id', $category_id);
                        $stmtUpdate->execute();
                    } else {

                        if (
                            $existingItem['Price'] != $price || $existingItem['Available'] != $available ||
                            $existingItem['regularPrice'] != $regularPrice || $existingItem['goldPrice'] != $goldPrice ||
                            $existingItem['platinumPrice'] != $platinumPrice || $existingItem['CategoryID'] != $category_id ||
                            $existingItem['QuantityMin'] != $qty_min || $existingItem['QuantityMax'] != $qty_max ||
                            $existingItem['ProductType'] != $product_type || $existingItem['ParentID'] != $category_id
                        ) {

                            $updateSql = "UPDATE items SET 
                        Name = :name, Price = :price, regularPrice = :regularPrice, goldPrice = :goldPrice, 
                        platinumPrice = :platinumPrice, CategoryID = :category_id, Available = :available, 
                        QuantityMin = :qty_min, QuantityMax = :qty_max, ProductType = :product_type, ParentID = :parent_id 
                        WHERE ItemID = :id";
                            $stmtUpdate = $pdo->prepare($updateSql);
                            $stmtUpdate->bindParam(':id', $id);
                            $stmtUpdate->bindParam(':name', $name);
                            $stmtUpdate->bindParam(':price', $price);
                            $stmtUpdate->bindParam(':regularPrice', $regularPrice);
                            $stmtUpdate->bindParam(':goldPrice', $goldPrice);
                            $stmtUpdate->bindParam(':platinumPrice', $platinumPrice);
                            $stmtUpdate->bindParam(':category_id', $category_id);
                            $stmtUpdate->bindParam(':available', $available);
                            $stmtUpdate->bindParam(':qty_min', $qty_min);
                            $stmtUpdate->bindParam(':qty_max', $qty_max);
                            $stmtUpdate->bindParam(':product_type', $product_type);
                            $stmtUpdate->bindParam(':parent_id', $category_id);
                            $stmtUpdate->execute();
                        }
                    }
                } else {
                    // Insert new item
                    $insertSql = "INSERT INTO items (ItemID, Name, Price, regularPrice, goldPrice, platinumPrice, CategoryID, Available, QuantityMin, QuantityMax, ProductType, ParentID, fromApi)
                VALUES (:id, :name, :price, :regularPrice, :goldPrice, :platinumPrice, :category_id, :available, :qty_min, :qty_max, :product_type, :parent_id, 1)";
                    $stmtInsert = $pdo->prepare($insertSql);
                    $stmtInsert->bindParam(':id', $id);
                    $stmtInsert->bindParam(':name', $name);
                    $stmtInsert->bindParam(':price', $price);
                    $stmtInsert->bindParam(':regularPrice', $regularPrice);
                    $stmtInsert->bindParam(':goldPrice', $goldPrice);
                    $stmtInsert->bindParam(':platinumPrice', $platinumPrice);
                    $stmtInsert->bindParam(':category_id', $category_id);
                    $stmtInsert->bindParam(':available', $available);
                    $stmtInsert->bindParam(':qty_min', $qty_min);
                    $stmtInsert->bindParam(':qty_max', $qty_max);
                    $stmtInsert->bindParam(':product_type', $product_type);
                    $stmtInsert->bindParam(':parent_id', $category_id);
                    $stmtInsert->execute();
                }

                // Insert or update item parameters
                foreach ($item['params'] as $param) {
                    $checkSql = "SELECT COUNT(*) FROM ItemParameters WHERE ItemID = :ItemID AND ParameterName = :ParameterName";
                    $stmtCheckParam = $pdo->prepare($checkSql);
                    $stmtCheckParam->bindParam(':ItemID', $id);
                    $stmtCheckParam->bindParam(':ParameterName', $param);
                    $stmtCheckParam->execute();
                    $count = $stmtCheckParam->fetchColumn();

                    if ($count == 0) {
                        $paramSql = "INSERT INTO ItemParameters (ItemID, ParameterName) VALUES (:ItemID, :ParameterName)";
                        $stmtParam = $pdo->prepare($paramSql);
                        $stmtParam->bindParam(':ItemID', $id);
                        $stmtParam->bindParam(':ParameterName', $param);
                        $stmtParam->execute();
                    }
                }

                // Handle specificPackage product type
                if ($product_type === 'specificPackage') {
                    $stmtDeleteSpecific = $pdo->prepare("DELETE FROM SpecificPackages WHERE ItemID = :ItemID");
                    $stmtDeleteSpecific->bindParam(':ItemID', $id);
                    $stmtDeleteSpecific->execute();

                    foreach ($item['qty_values'] as $qty) {
                        $stmtSpecificPackage = $pdo->prepare("INSERT INTO SpecificPackages (ItemID, Quantity) VALUES (:ItemID, :Quantity)");
                        $stmtSpecificPackage->bindParam(':ItemID', $id);
                        $stmtSpecificPackage->bindParam(':Quantity', $qty);
                        $stmtSpecificPackage->execute();
                    }
                }

                $response['data'][] = array('id' => $id, 'status' => 'synced');
            }

            // disable items that are not in the API data and have fromApi = 1
            if (!empty($apiItemIds)) {
                $ids = implode(',', array_map('intval', $apiItemIds)); // Convert IDs to a comma-separated string

                $deleteSql = "UPDATE items SET is_active = 0 WHERE fromApi = 1 AND ItemID NOT IN ($ids)";
                $pdo->exec($deleteSql);


            }

            echo json_encode($response);

        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }

        exit(); // Ensure no further output
    }





    if ($postjson["method"] == "login") {
        // Validate input fields 
        if (empty($postjson['userEmail']) || empty($postjson['userPassword'])) {
            http_response_code(400); // Bad Request
            echo json_encode(array('success' => false, 'message' => 'All fields are required..'));
            exit;
        }
        // Check if the email, username, and company code combination already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userEmail = ? ");
        $stmt->execute([$postjson['userEmail']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($user)) {
            // http_response_code(400); // Bad Request
            $response = array(
                'success' => false,
                'message' => 'User is not exists.'
            );
        } else {
            // Verify the provided password against the stored hashed password
            // $hashedPassword = password_hash($postjson['userPassword'], PASSWORD_DEFAULT);
            // echo $postjson['userPassword']."//".$user['userPassword']."/////";

            if (password_verify($postjson['userPassword'], $user['userPassword'])) {
                $response = array(
                    'success' => true,
                    'message' => 'Login successful.',
                    'user' => $user
                );
            } else {
                $response = array(
                    'success' => false,
                    'message' => 'Incorrect password.'
                );
            }
        }
        echo json_encode($response);
    }

    if ($postjson["method"] == "view-items") {
        try {
            // Prepare the SQL statement with a placeholder for the userRole
            $stmt = $pdo->prepare("SELECT * FROM items");

            // Execute the statement
            if ($stmt->execute()) {
                // Fetch the results as an associative array
                $dataInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Check if there are results
                if ($dataInfo) {
                    $result = json_encode(array('success' => true, 'items' => $dataInfo));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'No items found'));
                }
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "view-cards") {
        try {
            // Prepare the SQL statement with a placeholder for the userRole
            $stmt = $pdo->prepare("SELECT * FROM items WHERE is_stock_item = 1");

            // Execute the statement
            if ($stmt->execute()) {
                // Fetch the results as an associative array
                $dataInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Check if there are results
                if ($dataInfo) {
                    $result = json_encode(array('success' => true, 'items' => $dataInfo));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'No items found'));
                }
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    if ($postjson["method"] == "view-card-details") {
        try {
            // Prepare the SQL statement with a placeholder for the userRole
            $stmt = $pdo->prepare("SELECT details FROM card_details WHERE item_id = ? AND takenBy_user_id IS NULL");

            // Execute the statement
            if ($stmt->execute([$postjson['itemId']])) {
                // Fetch the results as an associative array
                $dataInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Check if there are results
                if ($dataInfo) {
                    $result = json_encode(array('success' => true, 'cardDetials' => $dataInfo));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'No items found'));
                }
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "item-details") {
        try {
            // Prepare the SQL statement with a placeholder for the userRole
            $stmt = $pdo->prepare("SELECT * FROM items WHERE ItemID = ?");

            // Execute the statement
            if ($stmt->execute([$postjson['itemId']])) {
                // Fetch the results as an associative array
                $dataInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Check if there are results
                if ($dataInfo) {
                    $result = json_encode(array('success' => true, 'itemDetails' => $dataInfo));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'No items found'));
                }
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "add-card-details") {
        try {
            $item_id = $postjson['itemId'];
            $details = $postjson['cardDetails']; // Assuming this is the complete string with multiple card details

            // Delete old card details for this item
            $stmtDelete = $pdo->prepare("DELETE FROM card_details WHERE item_id = :item_id AND takenBy_user_id IS NULL");
            $stmtDelete->bindParam(':item_id', $item_id);
            $stmtDelete->execute();

            // Split the details string by '##'
            $cards = explode('##', $details);

            // Prepare the SQL statement for insertion
            $stmt = $pdo->prepare("INSERT INTO card_details (item_id, details) VALUES (:item_id, :details)");
            $addedCardCount = 0; // Counter for the number of added cards

            foreach ($cards as $card) {
                $trimmedCard = trim($card); // Trim any extra whitespace
                if (!empty($trimmedCard)) { // Ensure the card details are not empty
                    $stmt->bindParam(':item_id', $item_id);
                    $stmt->bindParam(':details', $trimmedCard);
                    $stmt->execute();
                    $addedCardCount++; // Increment the counter for each added card
                }
            }

            // Update the item_qty_In_stock in the items table based on the number of added cards
            $stmtUpdateQty = $pdo->prepare("UPDATE items SET item_qty_In_stock = :newQty WHERE ItemID = :item_id");
            $stmtUpdateQty->bindParam(':newQty', $addedCardCount);
            $stmtUpdateQty->bindParam(':item_id', $item_id);
            $stmtUpdateQty->execute();

            $response = array(
                'success' => true,
                'msg' => 'Card details updated successfully!'
            );
        } catch (Exception $e) {
            $response = array(
                'success' => false,
                'msg' => $e->getMessage()
            );
        }

        echo json_encode($response);
    }




    function getApiResponse($url, $apiToken)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Add the API token to the headers
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'Authorization: Bearer ' . $apiToken, // Adjust the header according to API documentation
        // ]);

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'api-token: b091e0e21a9d098d10a9cb95d2a2aaa4e9c1b67efb9136d7',
                'Content-Type: application/json'
            )
        );

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return ['success' => false, 'msg' => "Failed to fetch statuses for orders", 'error' => $curlError];
        }

        return json_decode($response, true);
    }

    if ($postjson["method"] == "get-credit") {
        try {

            $apiUrl = "https://api.fastycard.com/client/api/profile";

            // Your API token
            $apiToken = 'b091e0e21a9d098d10a9cb95d2a2aaa4e9c1b67efb9136d7';
            $data = getApiResponse($apiUrl, $apiToken);
            echo json_encode(array('success' => true, 'credit' => $data));


        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "reject-order") {
        try {

            // Begin a transaction
            $pdo->beginTransaction();

            // Prepare the SQL statements
            $stmt = $pdo->prepare("UPDATE orders SET orderStatus = 'rejected', noteFromAdmin = ? WHERE orderId = ?");
            $stmtCredit = $pdo->prepare("SELECT orderTotal FROM orders WHERE orderId = ?");
            $userIdStmt = $pdo->prepare("SELECT orderUserId FROM orders WHERE orderId = ?");
            $userBalanceStmt = $pdo->prepare("SELECT userBalance FROM users WHERE userId = ?");
            $updateUserCreditStmt = $pdo->prepare("UPDATE users SET userBalance = ? WHERE userId = ?");

            // Execute the statements
            $stmtCredit->execute([$postjson["orderId"]]);
            $credit = $stmtCredit->fetchColumn();

            $userIdStmt->execute([$postjson["orderId"]]);
            $userId = $userIdStmt->fetchColumn();

            $userBalanceStmt->execute([$userId]);
            $userBalance = $userBalanceStmt->fetchColumn();
            $newUserCredit = $userBalance + $credit;

            // Update the order status and the user's balance
            if ($stmt->execute([$postjson["orderId"], $postjson["note"]]) && $updateUserCreditStmt->execute([$newUserCredit, $userId])) {
                // Commit the transaction
                $pdo->commit();
                $result = json_encode(array('success' => true, 'msg' => 'Order status updated to rejected!'));
            } else {
                // Rollback the transaction if something goes wrong
                $pdo->rollBack();
                $result = json_encode(array('success' => false, 'msg' => 'Error in updating order status!'));
            }

            echo $result;
        } catch (PDOException $e) {
            // Rollback the transaction in case of an exception
            $pdo->rollBack();
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    if ($postjson["method"] == "complete-order") {
        try {
            // Prepare the SQL statement with a placeholder for the userRole
            $stmt = $pdo->prepare("UPDATE orders SET orderStatus = 'completed'  WHERE orderId=?");

            // Execute the statement
            if ($stmt->execute([$postjson["orderId"]])) {

                $result = json_encode(array('success' => true, 'msg' => 'updateing order status to completed!'));


            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error in updateing order status!'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "refresh-orders") {
        try {
            $stmt = $pdo->prepare("SELECT orderIdFromApi FROM orders WHERE statusFromApi = 'wait'");
            if ($stmt->execute()) {
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Collect all order IDs in an array
                $orderIds = array_column($orders, 'orderIdFromApi');
                if (!empty($orderIds)) {
                    $orderIdsString = '[' . implode(',', array_map(fn($id) => '"' . $id . '"', $orderIds)) . ']';
                    $apiUrl = "https://api.fastycard.com/client/api/check?orders=$orderIdsString";

                    // Your API token
                    $apiToken = 'b091e0e21a9d098d10a9cb95d2a2aaa4e9c1b67efb9136d7';

                    // Get the API response
                    $data = getApiResponse($apiUrl, $apiToken);

                    if ($data['status'] != 'OK') {
                        echo json_encode($data);
                        return;
                    }

                    if ($data === null || $data['status'] !== 'OK' || empty($data['data'])) {
                        echo json_encode(array('success' => false, 'msg' => "Invalid response or no data"));
                        return;
                    }

                    // Process each order in the response
                    foreach ($data['data'] as $orderData) {
                        $orderId = $orderData['order_id'];
                        $apiStatus = $orderData['status'];

                        // Update the database for each order
                        $stmt = $pdo->prepare("UPDATE orders SET statusFromApi = :status WHERE orderIdFromApi = :orderIdFromApi");
                        $stmt->bindParam(':status', $apiStatus);
                        $stmt->bindParam(':orderIdFromApi', $orderId);

                        if (!$stmt->execute()) {
                            echo json_encode(array('success' => false, 'msg' => "Error updating status for order ID: $orderId"));
                            return;
                        }
                    }

                    // Output success message
                    echo json_encode(array('success' => true, 'msg' => "Orders have been refreshed!"));
                } else {
                    echo json_encode(array('success' => false, 'msg' => "No orders with status 'wait'"));
                }
            } else {
                echo json_encode(array('success' => false, 'msg' => "Error fetching orders"));
            }
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }




    if ($postjson["method"] == "view-clients") {
        try {
            // Prepare the SQL statement with a placeholder for the userRole
            $stmt = $pdo->prepare("SELECT userId, userFullname, userEmail, userAccountType, userBalance, userVerified FROM users WHERE userAccountType !='admin'");

            // Execute the statement
            if ($stmt->execute()) {
                // Fetch the results as an associative array
                $dataInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Check if there are results
                if ($dataInfo) {
                    $result = json_encode(array('success' => true, 'clients' => $dataInfo));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'No clients found'));
                }
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "client-status") {
        try {
            if ($postjson['clientStatus'] === 1) {
                $status = 0;
            } else {
                $status = 1;
            }
            $stmt = $pdo->prepare("UPDATE users SET userVerified = ? WHERE userId=?");
            if ($stmt->execute([$status, $postjson['clientId']])) {
                $result = json_encode(array('success' => true, 'msg' => 'client verfied status has been updated'));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error'));
            }
            echo $result;

        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "edit-type") {
        try {
            $clientId = $postjson["clientId"];
            $newType = $postjson["newType"];

            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("UPDATE users SET userAccountType = :userAccountType WHERE userId = :clientId");

            // Bind parameters
            $stmt->bindParam(':userAccountType', $newType);
            $stmt->bindParam(':clientId', $clientId);

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true, 'msg' => 'user Account Type has been updated.'));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "edit-credit") {
        try {
            $clientId = $postjson["clientId"];
            $newCredit = $postjson["newCredit"];

            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("UPDATE users SET userBalance = :userBalance WHERE userId = :clientId");

            // Bind parameters
            $stmt->bindParam(':userBalance', $newCredit);
            $stmt->bindParam(':clientId', $clientId);

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true, 'msg' => 'balance has been updated.'));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "custom-price") {
        try {
            $clientId = $postjson["clientId"];
            $itemId = $postjson["itemId"];
            $customPrice = $postjson["customPrice"];

            $checkCustomPriceStmt = $pdo->prepare("SELECT COUNT(*) FROM custompriceitem WHERE itemId=? AND userId=?");
            $checkCustomPriceStmt->execute([$itemId, $clientId]);
            $checkCustomPriceResult = $checkCustomPriceStmt->fetchColumn();
            if ($checkCustomPriceResult == 0) {
                // Prepare the SQL statement with placeholders
                $stmt = $pdo->prepare("INSERT INTO custompriceitem (itemId,userId,customPrice) VALUES(?,?,?)");



                // Execute the statement
                if ($stmt->execute([$itemId, $clientId, $customPrice])) {
                    $result = json_encode(array('success' => true, 'msg' => 'custome price inserted.'));
                } else {
                    $result = json_encode(array('success' => false));
                }

                echo $result;
            } else {
                $updateCustomPriceStmt = $pdo->prepare("UPDATE custompriceitem SET customPrice=? WHERE itemId=? AND userId=?");
                if ($updateCustomPriceStmt->execute([$customPrice, $itemId, $clientId])) {
                    $result = json_encode(array('success' => true, 'msg' => 'custom price updated!'));
                } else {
                    $result = json_encode(array('success' => false));
                }
                echo $result;
            }
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "get-admin") {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userId = ? ");
        $stmt->execute([$postjson['adminId']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($user)) {
            http_response_code(400); // Bad Request
            $response = array(
                'success' => false,
                'message' => 'User is not exists.'
            );
        } else {
            // Verify the provided password against the stored hashed password
            // $hashedPassword = password_hash($postjson['userPassword'], PASSWORD_DEFAULT);
            // echo $postjson['userPassword']."//".$user['userPassword']."/////".$hashedPassword;


            $response = array(
                'success' => true,
                'message' => 'admin info retrived successful.',
                'user' => $user
            );

        }
        echo json_encode($response);
    }



    if ($postjson["method"] == "exchange-rate") {
        try {
            $stmtExchangeRate = $pdo->prepare("UPDATE home SET homeChangeRate=?");
            if ($stmtExchangeRate->execute([$postjson['exchangeRate']])) {
                $exchangeRate = $stmtExchangeRate->fetchColumn();
                $result = json_encode(array('success' => true, "exchangeRate" => $exchangeRate));

            } else {
                $result = json_encode(array('success' => false, "msg" => "data not fetch"));
            }
            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "change-mode") {
        try {
            $stmtExchangeRate = $pdo->prepare("UPDATE settings SET value = ? WHERE key_name = 'under_construction'");
            if ($stmtExchangeRate->execute([$postjson['mode']])) {
                $exchangeRate = $stmtExchangeRate->fetchColumn();
                $result = json_encode(array('success' => true));

            } else {
                $result = json_encode(array('success' => false, "msg" => "data not fetch"));
            }
            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "home-data") {
        try {

            $stmtTotalOrders = $pdo->prepare("SELECT COUNT(*) as totalOrders FROM orders");
            $stmtTotalItems = $pdo->prepare("SELECT COUNT(*) as totalItems FROM items WHERE is_active=1");
            // $stmtExchangeRate=$pdo->prepare("SELECT * FROM home");
            $stmtTotalIncome = $pdo->prepare("SELECT SUM(orderTotal) as totalPrice FROM orders");
            $stmtTotalCustomers = $pdo->prepare("SELECT COUNT(*) as totalCustomers from users where userAccountType !='admin'");
            $stmtconstructionMode = $pdo->prepare("SELECT * FROM settings WHERE key_name ='under_construction'");
            if ($stmtTotalOrders->execute() && $stmtTotalItems->execute() && $stmtTotalCustomers->execute() && $stmtTotalIncome->execute() && $stmtconstructionMode->execute()) {
                $totalOrders = $stmtTotalOrders->fetchColumn();
                $totalItems = $stmtTotalItems->fetchColumn();
                $totalCustomers = $stmtTotalCustomers->fetchColumn();
                $totalIncome = $stmtTotalIncome->fetchColumn();
                $constructionMode = $stmtconstructionMode->fetch();
                $result = json_encode(array('success' => true, "totalOrders" => $totalOrders, "totalItems" => $totalItems, "totalCustomers" => $totalCustomers, "totalIncome" => $totalIncome, "constructionMode" => $constructionMode));
            } else {
                $result = json_encode(array('success' => false, "msg" => "data not fetch"));
            }
            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }

    }



    if ($postjson["method"] == "edit-info") {
        try {
            $userId = $postjson["userId"];
            $userFullName = $postjson["userFullName"];
            $username = $postjson["username"];
            // $password = $postjson["password"];
            $password = password_hash($postjson['password'], PASSWORD_DEFAULT);


            // Check if another user with the same mobile number exists
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND userId != :userId");
            $stmtCheck->bindParam(':username', $username);
            $stmtCheck->bindParam(':userId', $userId);
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn();

            if ($count > 0) {
                // Another user with the same mobile number already exists, return a message
                $result = json_encode(array('success' => false, 'message' => 'error in user name'));
            } else {
                $stmt = $pdo->prepare("UPDATE users 
                               SET userFullname = :userFullName,  
                                   username = :username, 
                                   userPassword = :password
                               WHERE userId = :userId");

                $stmt->bindParam(':userFullName', $userFullName);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $password);
                $stmt->bindParam(':userId', $userId);

                if ($stmt->execute()) {
                    $result = json_encode(array('success' => true));
                } else {
                    $result = json_encode(array('success' => false));
                }
            }
            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "add-user") {
        // echo "sss";
        try {
            // Define the data to be inserted
            $userFullname = $postjson["userFullname"];
            $username = $postjson["username"];
            $password = password_hash($postjson['password'], PASSWORD_DEFAULT);

            $userType = 'admin';


            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("INSERT INTO users (userFullname, username , userPassword, userAccountType)
                               VALUES (:userFullname, :username , :password, :userAccountType)");

            // Bind parameters
            $stmt->bindParam(':userFullname', $userFullname);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':userAccountType', $userType);

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "delete-custom-price") {
        // echo "sss";
        try {


            $itemId = $postjson["itemId"];

            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("DELETE FROM custompriceitem WHERE itemId=:itemId");

            // Bind parameters

            $stmt->bindParam(':itemId', $itemId);

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "delete-marquee") {
        // echo "sss";
        try {

            $id = $postjson["id"];



            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("DELETE FROM marquees WHERE id=:id");

            // Bind parameters

            $stmt->bindParam(':id', $id);


            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "update-marquee") {
        // echo "sss";
        try {
            // Define the data to be inserted
            $content = $postjson["content"];
            $id = $postjson["id"];



            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("UPDATE marquees SET content=:content WHERE id=:id");

            // Bind parameters
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':id', $id);


            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "add-marquee") {
        // echo "sss";
        try {
            // Define the data to be inserted
            $content = $postjson["content"];



            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("INSERT INTO marquees (content)
                               VALUES (:content)");

            // Bind parameters
            $stmt->bindParam(':content', $content);


            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "view-workers") {
        try {
            // Prepare the SQL statement with a placeholder for the userRole
            $stmt = $pdo->prepare("SELECT * FROM users WHERE userRole = 'worker'");

            // Execute the statement
            if ($stmt->execute()) {
                // Fetch the results as an associative array
                $dataInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Check if there are results
                if ($dataInfo) {
                    $result = json_encode(array('success' => true, 'info' => $dataInfo));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'No workers found'));
                }
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "delete-worker") {
        try {
            // Define the worker ID to be deleted
            $workerId = $postjson["workerId"];


            $stmt = $pdo->prepare("DELETE FROM users WHERE userId = :workerId");

            // Bind parameters
            $stmt->bindParam(':workerId', $workerId);

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    if ($postjson["method"] == "view-groups") {
        try {


            $stmt = $pdo->prepare("SELECT * FROM groups");
            if ($stmt->execute()) {
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result = json_encode(array('success' => true, 'groups' => $categories));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }
            echo $result;


        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }

    }


    if ($postjson["method"] == "view-cats") {
        try {


            $stmt = $pdo->prepare("SELECT c.*, g.groupName 
            FROM categories c 
            LEFT JOIN groups g ON g.groupId = c.groupId
            WHERE c.isActive =1 AND EXISTS (
                SELECT 1 
                FROM items i 
                WHERE i.CategoryID = c.CategoryID AND i.is_active = 1
            )");
            if ($stmt->execute()) {
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result = json_encode(array('success' => true, 'categories' => $categories));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }
            echo $result;

        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }

    }


    if ($postjson["method"] == "get-sub-cat") {
        try {
            // Prepare an SQL query to retrieve categories and their subcategories
            $stmt = $pdo->prepare("SELECT * from subcategory WHERE subId=?");

            if ($stmt->execute([$postjson["subCatId"]])) {
                $subCategory = $stmt->fetch();
                $result = json_encode(array('success' => true, 'data' => $subCategory));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "view-sub-cat") {
        try {

            if ($postjson["catId"] == 'all') {
                $stmt = $pdo->prepare("SELECT s.*, c.catName from subcategory s INNER JOIN categories c ON c.catId=s.catId");
                if ($stmt->execute()) {
                    $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $result = json_encode(array('success' => true, 'data' => $subCategories));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
                }
            } else {
                // Prepare an SQL query to retrieve categories and their subcategories
                $stmt = $pdo->prepare("SELECT s.*, c.catName from subcategory s INNER JOIN categories c ON c.catId=s.catId WHERE s.catId=?");
                if ($stmt->execute([$postjson["catId"]])) {
                    $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $result = json_encode(array('success' => true, 'data' => $subCategories));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error fetching data'));
                }
            }



            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }


    }



    if ($postjson["method"] == "edit-cat") {
        try {
            // Define the category ID and name to be updated
            $catId = $postjson["catId"];
            $groupId = $postjson["groupId"];
            $catName = $postjson["catName"];
            echo $groupId;

            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("UPDATE categories SET catName = :catName, groupId = :groupId WHERE catId = :catId");

            // Bind parameters
            $stmt->bindParam(':catName', $catName);
            $stmt->bindParam(':catId', $catId);
            $stmt->bindParam(':groupId', $groupId);

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }



    // if ($postjson["method"] == "edit-sub-cat") {
//     try {
//         // Define the category ID and name to be updated
//         $subCatId = $postjson["subCatId"];
//         $subCatName = $postjson["subCatName"];
//         $subCatNameAr = $postjson["subCatNameAr"];

    //         // Prepare the SQL statement with placeholders
//         $stmt = $pdo->prepare("UPDATE subcategory SET subName = :subCatName, subNameAr = :subCatNameAr WHERE subId = :subCatId");

    //         // Bind parameters
//         $stmt->bindParam(':subCatName', $subCatName);
//         $stmt->bindParam(':subCatNameAr', $subCatNameAr);
//         $stmt->bindParam(':subCatId', $subCatId);

    //         // Execute the statement
//         if ($stmt->execute()) {
//             $result = json_encode(array('success' => true));
//         } else {
//             $result = json_encode(array('success' => false));
//         }

    //         echo $result;
//     } catch (PDOException $e) {
//         echo json_encode(array('success' => false, 'error' => $e->getMessage()));
//     }
// }

    if ($postjson["method"] == "send-notifications") {
        try {


            $notificationTitle = $postjson["notificationTitle"];
            $notificationMessage = $postjson["notificationMessage"];


            if (sendGCM($notificationTitle, $notificationMessage, 'users', 'none', 'none')) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error sending notifications'));
            }

            // Execute the statement
            // if ($stmt->execute()) {
            //     $result = json_encode(array('success' => true));
            // } else {
            //     $result = json_encode(array('success' => false, 'msg' => 'Error sending notifications'));
            // }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    // if ($postjson["method"] == "add-subcategory") {
//     try {

    //         // Define the subcategory name and category ID to be inserted
//         $subName = $postjson["subName"];
//         $subNameAr = $postjson["subNameAr"];
//         $catId = $postjson["catId"]; // The category ID associated with the subcategory

    //         // Prepare the SQL statement with placeholders
//         $stmt = $pdo->prepare("INSERT INTO subCategory (subName,subNameAr, catId) VALUES (:subName, :subNameAr, :catId)");

    //         // Bind parameters
//         $stmt->bindParam(':subName', $subName);
//         $stmt->bindParam(':subNameAr', $subNameAr);
//         $stmt->bindParam(':catId', $catId);

    //         // Execute the statement
//         if ($stmt->execute()) {
//             $result = json_encode(array('success' => true));
//         } else {
//             $result = json_encode(array('success' => false, 'msg' => 'Error inserting subcategory'));
//         }

    //         echo $result;
//     } catch (PDOException $e) {
//         echo json_encode(array('success' => false, 'error' => $e->getMessage()));
//     }
// }



    if ($postjson["method"] == "get-items-by-sub-cat-id") {
        try {
            // Initialize an array to hold the categories and subcategories
            // Your subcategory ID
            $subcategoryId = $postjson['subCatId'];

            if ($subcategoryId == 'all') {

                // Prepare an SQL query to retrieve items by subcategory ID
                $query = "SELECT * FROM items WHERE is_active = 1";

                // Prepare the SQL statement with placeholders
                $stmt = $pdo->prepare($query);



                // Execute the statement
                if ($stmt->execute()) {
                    // Fetch the results as an array of items
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // JSON encode and return the items
                    echo json_encode(['success' => true, 'items' => $items]);
                } else {
                    // Handle the case where the query fails
                    echo json_encode(['success' => false, 'message' => 'Failed to retrieve items']);
                }

            } else {
                // Prepare an SQL query to retrieve items by subcategory ID
                $query = "SELECT i.*, c.CategoryName FROM items i
                            LEFT JOIN categories c ON c.CategoryID = i.ParentID
                            WHERE i.ParentID = :subcategoryId AND is_active = 1";

                // Prepare the SQL statement with placeholders
                $stmt = $pdo->prepare($query);

                // Bind the subcategoryId parameter
                $stmt->bindParam(':subcategoryId', $subcategoryId, PDO::PARAM_INT);

                $categoryStmt = $pdo->prepare("SELECT CategoryName DEOM categories WHERE CategoryID = :CategoryID");

                $categoryStmt->bindParam(':CategoryID', $subcategoryId, PDO::PARAM_INT);
                $categoryStmt->execute();
                $categoryName = $categoryStmt->fetchColumn();

                // Execute the statement
                if ($stmt->execute()) {
                    // Fetch the results as an array of items
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // JSON encode and return the items
                    echo json_encode(['success' => true, 'items' => $items,"categoryName" =>$categoryName]);
                } else {
                    // Handle the case where the query fails
                    echo json_encode(['success' => false, 'message' => 'Failed to retrieve items']);
                }
            }



        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }

    }


    if ($postjson["method"] == "delete-item") {
        try {
            // Define the worker ID to be deleted
            $itemId = $postjson["itemId"];

            // Get the filename of the item photo before deletion
            $stmt = $pdo->prepare("SELECT itemPhoto FROM items WHERE itemId = :itemId");
            $stmt->bindParam(':itemId', $itemId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $filename = $row['itemPhoto'];


            $stmt = $pdo->prepare("UPDATE items SET is_active=0 WHERE itemId = :itemId");

            // Bind parameters
            $stmt->bindParam(':itemId', $itemId);

            // Execute the statement
            if ($stmt->execute()) {
                if ($filename && file_exists('uploads/' . $filename)) {
                    unlink('uploads/' . $filename);
                }
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($postjson["method"] == "delete-sub-cat") {
        try {
            // Define the subcategory ID to be deleted
            $subcategoryId = $postjson["subCatId"];

            // Get the filenames of the subcategory and associated item photos before deletion
            $stmt = $pdo->prepare("SELECT subPhoto FROM subcategory WHERE subId = :subcategoryId");
            $stmt->bindParam(':subcategoryId', $subcategoryId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $subcategoryPhoto = $row['subPhoto'];

            $stmt = $pdo->prepare("SELECT itemId, itemPhoto FROM items WHERE subId = :subcategoryId");
            $stmt->bindParam(':subcategoryId', $subcategoryId);
            $stmt->execute();
            $itemsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pdo->beginTransaction();

            // Delete all items associated with the subcategory
            $stmt = $pdo->prepare("DELETE FROM items WHERE subId = :subcategoryId");
            $stmt->bindParam(':subcategoryId', $subcategoryId);
            $stmt->execute();

            // Delete the subcategory from the subcategory table
            $stmt = $pdo->prepare("DELETE FROM subcategory WHERE subId = :subcategoryId");
            $stmt->bindParam(':subcategoryId', $subcategoryId);
            $stmt->execute();

            // Delete the subcategory photo
            if ($subcategoryPhoto && file_exists('uploads/' . $subcategoryPhoto)) {
                unlink('uploads/' . $subcategoryPhoto);
            }

            // Delete associated item photos
            foreach ($itemsData as $item) {
                if ($item['itemPhoto'] && file_exists('uploads/' . $item['itemPhoto'])) {
                    unlink('uploads/' . $item['itemPhoto']);
                }
            }

            // Commit the transaction
            $pdo->commit();

            $result = json_encode(array('success' => true));
            echo $result;
        } catch (PDOException $e) {
            // Roll back the transaction in case of an error
            $pdo->rollBack();
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }


    }

    if ($postjson["method"] == "delete-cat") {
        try {
            $categoryId = $postjson["catId"];
            $deactivateCategoryStmt=$pdo->prepare("UPDATE categories SET isActive = 0 WHERE CategoryID= ?");
            $deactivateCategoryStmt->bindParam(1, $categoryId);
            if($deactivateCategoryStmt->execute())
            {

                $result = json_encode(array('success' => true));
            }else{
                $result = json_encode(array('success' => false));
            }
            echo $result;

        } catch (PDOException $e) {

            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    // if ($postjson["method"] == "delete-cat") {
    //     try {
    //         // Start a database transaction
    //         $pdo->beginTransaction();

    //         // Define the category ID to be deleted
    //         $categoryId = $postjson["catId"];

    //         // Get the filenames of the category photo, subcategory photos, and item photos before deletion
    //         $stmt = $pdo->prepare("SELECT catPhoto FROM categories WHERE catId = :categoryId");
    //         $stmt->bindParam(':categoryId', $categoryId);
    //         $stmt->execute();
    //         $row = $stmt->fetch(PDO::FETCH_ASSOC);
    //         $categoryPhoto = $row['catPhoto'];

    //         $stmt = $pdo->prepare("SELECT subId, subPhoto FROM subcategory WHERE catId = :catId");
    //         $stmt->bindParam(':catId', $categoryId); // Changed to categoryId
    //         $stmt->execute();
    //         $subcategoriesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //         $stmt = $pdo->prepare("SELECT itemId, itemPhoto FROM items WHERE subId IN (SELECT subId FROM subcategory WHERE catId = :catId)");
    //         $stmt->bindParam(':catId', $categoryId); // Changed to categoryId
    //         $stmt->execute();
    //         $itemsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //         // Delete all items associated with the subcategories
    //         $stmt = $pdo->prepare("DELETE FROM items WHERE subId IN (SELECT subId FROM subcategory WHERE catId = :catId)");
    //         $stmt->bindParam(':catId', $categoryId); // Changed to categoryId
    //         $stmt->execute();

    //         // Delete all subcategories associated with the category
    //         $stmt = $pdo->prepare("DELETE FROM subcategory WHERE catId = :catId");
    //         $stmt->bindParam(':catId', $categoryId);
    //         $stmt->execute();

    //         // Delete the category from the categories table
    //         $stmt = $pdo->prepare("DELETE FROM categories WHERE catId = :categoryId");
    //         $stmt->bindParam(':categoryId', $categoryId);
    //         $stmt->execute();

    //         // Delete the category photo
    //         if ($categoryPhoto && file_exists('uploads/' . $categoryPhoto)) {
    //             unlink('uploads/' . $categoryPhoto);
    //         }

    //         // Delete associated subcategory photos
    //         foreach ($subcategoriesData as $subcategory) {
    //             if ($subcategory['subPhoto'] && file_exists('uploads/' . $subcategory['subPhoto'])) {
    //                 unlink('uploads/' . $subcategory['subPhoto']);
    //             }
    //         }

    //         // Delete associated item photos
    //         foreach ($itemsData as $item) {
    //             if ($item['itemPhoto'] && file_exists('uploads/' . $item['itemPhoto'])) {
    //                 unlink('uploads/' . $item['itemPhoto']);
    //             }
    //         }

    //         // Commit the transaction
    //         $pdo->commit();

    //         $result = json_encode(array('success' => true));
    //         echo $result;
    //     } catch (PDOException $e) {
    //         // Roll back the transaction in case of an error
    //         $pdo->rollBack();
    //         echo json_encode(array('success' => false, 'error' => $e->getMessage()));
    //     }
    // }




    if ($postjson["method"] == "get-item") {
        $stmt = $pdo->prepare("SELECT i.*, p.ParameterName FROM items i LEFT JOIN itemparameters p ON p.ItemID=i.ItemID  WHERE i.ItemID = ? ");
        $stmt->execute([$postjson['itemId']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($item)) {
            http_response_code(400); // Bad Request
            $response = array(
                'success' => false,
                'message' => 'item is not exists.'
            );
        } else {
            // Verify the provided password against the stored hashed password
            // $hashedPassword = password_hash($postjson['userPassword'], PASSWORD_DEFAULT);
            // echo $postjson['userPassword']."//".$user['userPassword']."/////".$hashedPassword;


            $response = array(
                'success' => true,
                'message' => 'item info retrived successful.',
                'data' => $item
            );

        }
        echo json_encode($response);
    }

    // if ($postjson["method"] == "add-item") {
//     try {

    //         // Define the subcategory name and category ID to be inserted
//         $itemName = $postjson["itemName"];
//         $itemText = $postjson["itemText"];
//         $itemPrice = $postjson["itemPrice"];
//         $subCatId = $postjson["subCatId"]; // The category ID associated with the subcategory

    //         // Prepare the SQL statement with placeholders
//         $stmt = $pdo->prepare("INSERT INTO items (itemName, itemText, subId, itemPrice) VALUES (:itemName, :itemText, :subId, :itemPrice)");

    //         // Bind parameters
//         $stmt->bindParam(':itemName', $itemName);
//         $stmt->bindParam(':itemText', $itemText);
//         $stmt->bindParam(':subId', $subCatId);
//         $stmt->bindParam(':itemPrice', $itemPrice);

    //         // Execute the statement
//         if ($stmt->execute()) {
//             $result = json_encode(array('success' => true));
//         } else {
//             $result = json_encode(array('success' => false, 'msg' => 'Error inserting subcategory'));
//         }

    //         echo $result;
//     } catch (PDOException $e) {
//         echo json_encode(array('success' => false, 'error' => $e->getMessage()));
//     }
// }

    if ($postjson["method"] == "client-orders") {
        $stmt = $pdo->prepare("SELECT o.*, i.Name , u.userFullname, u.userEmail
                                       FROM orders o
                                       LEFT JOIN items i ON i.ItemID = o.orderItemId 
                                       LEFT JOIN users u ON u.userId = o.orderUserId
                                       WHERE o.orderUserId=?
    ");
        $stmt->execute([$postjson['clientId']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtUser = $pdo->prepare("SELECT * FROM users WHERE userId=?");
        $stmtUser->execute([$postjson['clientId']]);
        $userInfo = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (empty($orders)) {
            $response = array(
                'success' => false,
                'userInfo' => $userInfo,
                'message' => 'there is no orders.'
            );
            // http_response_code(400); // Bad Request
        } else {
            $response = array(
                'success' => true,
                'userInfo' => $userInfo,
                'message' => 'user and orders info retrived successful.',
                'data' => $orders
            );

        }
        echo json_encode($response);
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

    if ($postjson["method"] == "view-user-info") {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userId=?");
        $stmt->execute([$postjson['clientId']]);
        $userInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($userInfo)) {
            // http_response_code(400); // Bad Request

            $response = array(
                'success' => false,
                'message' => 'there is no user info.'
            );
        } else {

            $response = array(
                'success' => true,
                'message' => 'info retrived successful.',
                'userInfo' => $userInfo
            );

        }
        echo json_encode($response);
    }

    if ($postjson["method"] == "view-custom-price") {
        $stmt = $pdo->prepare("SELECT i.itemID, i.Name, i.Price, c.customPrice, u.userId, u.userFullname , u.userEmail, u.userAccountType, u.userBalance 
                                       FROM custompriceitem c
                                       LEFT JOIN users u ON u.userId = c.userId
                                       LEFT JOIN items i ON i.itemID = c.itemId
                                       WHERE c.userId=?");
        $stmt->execute([$postjson['userId']]);
        $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($prices)) {
            // http_response_code(400); // Bad Request

            $response = array(
                'success' => false,
                'message' => 'there is no custom price.'
            );
        } else {

            $response = array(
                'success' => true,
                'message' => 'info retrived successful.',
                'prices' => $prices
            );

        }
        echo json_encode($response);
    }

    if ($postjson["method"] == "view-payments") {
        $stmt = $pdo->prepare("SELECT p.*, u.userFullname , u.userEmail, u.userAccountType, u.userBalance 
                                       FROM payments p
                                       LEFT JOIN users u ON u.userId = p.paymentUserId
                                       ORDER BY p.paymentId DESC");
        $stmt->execute();
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($payments)) {
            http_response_code(400); // Bad Request
            $response = array(
                'success' => false,
                'message' => 'there is no payments.'
            );
        } else {

            $response = array(
                'success' => true,
                'message' => 'info retrived successful.',
                'payments' => $payments
            );

        }
        echo json_encode($response);
    }

    if ($postjson["method"] == "proceed-payments") {
        try {
            // Begin a transaction
            $pdo->beginTransaction();

            // Fetch user balance
            $userBalanceStmt = $pdo->prepare("SELECT userBalance FROM users WHERE userId = ?");
            $userBalanceStmt->execute([$postjson["userId"]]);
            $userBalance = $userBalanceStmt->fetchColumn();

            if ($userBalance === false) {
                throw new Exception('User not found.');
            }

            // Fetch payment value
            $userPaymentStmt = $pdo->prepare("SELECT paymentValue FROM payments WHERE paymentId = ?");
            $userPaymentStmt->execute([$postjson["paymentId"]]);
            $userPayment = $userPaymentStmt->fetchColumn();

            if ($userPayment === false) {
                throw new Exception('Payment not found.');
            }

            // Calculate new balance
            $newUserBalance = $userBalance + $userPayment;

            // Update user balance
            $stmt = $pdo->prepare("UPDATE users SET userBalance = :newBalance WHERE userId = :userId");
            $stmt->bindParam(':newBalance', $newUserBalance);
            $stmt->bindParam(':userId', $postjson["userId"]);

            $paymentStmt = $pdo->prepare("UPDATE payments SET paymentStatus='done' WHERE paymentId = :paymentId");
            $paymentStmt->bindParam(':paymentId', $postjson["paymentId"]);

            if ($stmt->execute() && $paymentStmt->execute()) {
                // Commit the transaction
                $pdo->commit();

                $response = array(
                    'success' => true,
                    'msg' => 'Account has been recharged!'
                );
            } else {
                throw new Exception('Error in updating user balance.');
            }
        } catch (Exception $e) {
            // Rollback the transaction if something went wrong
            $pdo->rollBack();

            // Log the exception for debugging (optional)
            // error_log($e->getMessage());

            $response = array(
                'success' => false,
                'msg' => $e->getMessage()
            );
        }

        echo json_encode($response);
    }

    if ($postjson["method"] == "cancel-payments") {
        try {

            $paymentStmt = $pdo->prepare("UPDATE payments SET paymentStatus='canceled' WHERE paymentId = :paymentId");
            $paymentStmt->bindParam(':paymentId', $postjson["paymentId"]);

            if ($paymentStmt->execute()) {


                $response = array(
                    'success' => true,
                    'msg' => 'Payment has been canceled.'
                );
            } else {

                $response = array(
                    'success' => false,
                    'msg' => 'Failed to cancel the payment.'
                );
            }
        } catch (Exception $e) {
            // Catch any exceptions and prepare an error response
            $response = array(
                'success' => false,
                'msg' => 'An error occurred: ' . $e->getMessage()
            );
        }

        echo json_encode($response);
    }

    if ($postjson["method"] == "view-orders") {
        $stmt = $pdo->prepare("SELECT o.*, i.Name , u.userFullname, u.userEmail
                                       FROM orders o
                                       LEFT JOIN items i ON i.ItemID = o.orderItemId 
                                       LEFT JOIN users u ON u.userId = o.orderUserId");
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orders)) {
            http_response_code(400); // Bad Request
            $response = array(
                'success' => false,
                'message' => 'there is no orders.'
            );
        } else {



            $response = array(
                'success' => true,
                'message' => 'user and orders info retrived successful.',
                'data' => $orders
            );

        }
        echo json_encode($response);
    }

    if ($postjson["method"] == "order-details") {
        $stmt = $pdo->prepare("SELECT * FROM ordersdetailsview WHERE orderId= ?
    ");
        $stmt->execute([$postjson['orderId']]);
        $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orderDetails)) {
            http_response_code(400); // Bad Request
            $response = array(
                'success' => false,
                'message' => 'there is no order details.'
            );
        } else {



            $response = array(
                'success' => true,
                'message' => 'order details retrived successful.',
                'data' => $orderDetails
            );

        }
        echo json_encode($response);
    }

}


if (isset($_POST["method"])) {

    if ($_POST["method"] == "add-group") {
        try {

            $groupName = $_POST["groupName"];

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            }



            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("INSERT INTO groups ( groupName, groupPhoto) VALUES (:groupName, :groupPhoto)");

            // Bind parameters
            $stmt->bindParam(':groupName', $groupName);

            $stmt->bindParam(':groupPhoto', $fileName); // Store the file name in the database

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error inserting item'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($_POST["method"] == "add-cat") {
        try {

            // Define the item name, item text, item price, and subcategory ID to be inserted
            $catName = $_POST["catName"];

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            }

            // Step 1: Get the maximum CategoryID
            $sqlMax = "SELECT MAX(CategoryID) AS max_id FROM categories";
            $stmtMax = $pdo->query($sqlMax);
            $maxID = $stmtMax->fetch(PDO::FETCH_ASSOC)['max_id'];

            // Step 2: Increment the max CategoryID by 1
            $newCategoryID = $maxID + 1;

            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("INSERT INTO categories (CategoryID, CategoryName, catPhoto) VALUES (:CategoryID, :catName, :catPhoto)");

            // Bind parameters
            $stmt->bindParam(':catName', $catName);
            $stmt->bindParam(':CategoryID', $newCategoryID);

            $stmt->bindParam(':catPhoto', $fileName); // Store the file name in the database

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error inserting item'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($_POST["method"] == "add-subcategory") {
        try {
            // formData.append('catId', catId);
            // formData.append('subName', subName);
            // formData.append('subNameAr', subNameAr);
            // Define the item name, item text, item price, and subcategory ID to be inserted
            $catId = $_POST["catId"];
            $subName = $_POST["subName"];
            $subNameAr = $_POST["subNameAr"];

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            }

            // Prepare the SQL statement with placeholders

            $stmt = $pdo->prepare("INSERT INTO subCategory (subName, subNameAr, catId, subPhoto) VALUES (:subName, :subNameAr, :catId, :subPhoto)");

            // Bind parameters
            $stmt->bindParam(':subName', $subName);
            $stmt->bindParam(':subNameAr', $subNameAr);
            $stmt->bindParam(':catId', $catId);
            $stmt->bindParam(':subPhoto', $fileName); // Store the file name in the database

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error inserting item'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    // if ($_POST["method"] == "add-subcategory") {

    //     try {


    //         $subName = $_POST["subName"];
    //         $catId = $_POST["catId"];

    //         // Handle file upload
    //         if (isset($_FILES['fileSource'])) {
    //             $file = $_FILES['fileSource'];
    //             $fileName = $file['name'];
    //             $fileTmpName = $file['tmp_name'];
    //             $fileError = $file['error'];

    //             // Check if there was no file upload error
    //             if ($fileError === UPLOAD_ERR_OK) {
    //                 // Define the directory where you want to save the uploaded files
    //                 $uniqueId = uniqid(); // Generate a unique ID
    //                 $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
    //                 $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

    //                 $uploadDirectory = 'uploads/' . $newFileName;

    //                 // Move the uploaded file to the specified directory
    //                 if (move_uploaded_file($fileTmpName, $uploadDirectory)) {
    //                     // The file was successfully uploaded
    //                     $fileName = $newFileName; // Update the file name to the new unique name
    //                 } else {
    //                     // Handle the case where file upload failed
    //                     $result = json_encode(array('success' => false, 'msg' => 'Error moving uploaded file'));
    //                     echo $result;
    //                     exit;
    //                 }
    //             } else {
    //                 // Handle the case where file upload had an error
    //                 $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
    //                 echo $result;
    //                 exit;
    //             }
    //         }

    //         // Prepare the SQL statement with placeholders
    //         $stmt = $pdo->prepare("INSERT INTO subcategory (subName, catId, subPhoto) VALUES (:subName, :catId, :subPhoto)");

    //         // Bind parameters
    //         $stmt->bindParam(':subName', $subName);
    //         $stmt->bindParam(':catId', $catId);
    //         $stmt->bindParam(':subPhoto', $fileName); // Store the file name in the database

    //         // Execute the statement
    //         if ($stmt->execute()) {
    //             $result = json_encode(array('success' => true));
    //         } else {
    //             $result = json_encode(array('success' => false, 'msg' => 'Error inserting item'));
    //         }

    //         echo $result;
    //     } catch (PDOException $e) {
    //         echo json_encode(array('success' => false, 'error' => $e->getMessage()));
    //     }
    // }


    if ($_POST["method"] == "add-item") {
        try {


            // formData.append('regularPrice', regularPrice);
            // formData.append('goldPrice', goldPrice);
            // formData.append('platinumPrice', platinumPrice);
            // Define the item name, item text, item price, and subcategory ID to be inserted
            $Name = $_POST["Name"];
            $Price = $_POST["Price"];
            $param = $_POST["param"];
            $regularPrice = $_POST["regularPrice"];
            $goldPrice = $_POST["goldPrice"];
            $platinumPrice = $_POST["platinumPrice"];
            $Available = $_POST["Available"];
            $ProductType = $_POST["ProductType"];
            $QuantityMin = $_POST["QuantityMin"];
            $QuantityMax = $_POST["QuantityMax"];
            $catId = $_POST["catId"]; // The category ID associated with the item
            $is_stock_item = $_POST["isStock"];
            $item_qty_In_stock = $_POST["cardQty"];

            if ($QuantityMin == 0 && $QuantityMax == 0) {
                $QuantityMin = null;
                $QuantityMax = null;
            }
            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            }
            // Step 1: Get the maximum CategoryID
            $sqlMax = "SELECT MAX(ItemID) AS max_id FROM items";
            $stmtMax = $pdo->query($sqlMax);
            $maxID = $stmtMax->fetch(PDO::FETCH_ASSOC)['max_id'];

            // Step 2: Increment the max CategoryID by 1
            $newItemID = $maxID + 1;

            // Prepare the SQL statement with placeholders
            $stmt = $pdo->prepare("INSERT INTO items (ItemID, CategoryID, ParentID, Name, Price, regularPrice, goldPrice, platinumPrice, Available, ProductType, QuantityMin, QuantityMax,  itemPhoto, is_stock_item, item_qty_In_stock) VALUES (:ItemID, :CategoryID, :ParentID, :Name, :Price, :regularPrice, :goldPrice, :platinumPrice,  :Available, :ProductType,:QuantityMin, :QuantityMax, :itemPhoto, :is_stock_item, :item_qty_In_stock)");

            // Bind parameters
            $stmt->bindParam(':ItemID', $newItemID);
            $stmt->bindParam(':CategoryID', $catId);
            $stmt->bindParam(':ParentID', $catId);
            $stmt->bindParam(':Name', $Name);
            $stmt->bindParam(':Price', $Price);
            $stmt->bindParam(':regularPrice', $regularPrice);
            $stmt->bindParam(':goldPrice', $goldPrice);
            $stmt->bindParam(':platinumPrice', $platinumPrice);
            $stmt->bindParam(':Available', $Available);
            $stmt->bindParam(':ProductType', $ProductType);
            $stmt->bindParam(':QuantityMin', $QuantityMin);
            $stmt->bindParam(':QuantityMax', $QuantityMax);
            $stmt->bindParam(':is_stock_item', $is_stock_item);
            $stmt->bindParam(':item_qty_In_stock', $item_qty_In_stock);

            $stmt->bindParam(':itemPhoto', $fileName); // Store the file name in the database

            $paramStmt = $pdo->prepare("INSERT INTO itemparameters (ItemID, ParameterName) VALUES(:itemId, :param)");
            $paramStmt->bindParam(':itemId', $newItemID);
            $paramStmt->bindParam(':param', $param);


            // Execute the statement
            if ($stmt->execute() && $paramStmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error inserting item'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }



    if ($_POST["method"] == "edit-item") {
        try {


            // $itemName = $_POST["itemName"];
            // $itemNameAr = $_POST["itemNameAr"];
            // $itemText = $_POST["itemText"];
            // $itemTextAr = $_POST["itemTextAr"];
            // $itemPrice = $_POST["itemPrice"];
            // $itemBarcode = $_POST["itemBarcode"];
            // $itemDiscount = $_POST["itemDiscount"];
            $itemId = $_POST["itemId"];
            $autoProceed = $_POST["autoProceed"];
            $available = $_POST["available"];
            $param = $_POST["param"];
            $catId = $_POST["catId"];
            $regularPrice = $_POST["regularPrice"];
            $goldPrice = $_POST["goldPrice"];
            $platinumPrice = $_POST["platinumPrice"];



            $newFileName = null; // Initialize the new file name

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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

                    // Check if an old file exists and remove it
                    $stmt = $pdo->prepare("SELECT itemPhoto FROM items WHERE ItemID = :itemId");
                    $stmt->bindParam(':itemId', $itemId);
                    $stmt->execute();
                    $oldFileName = $stmt->fetchColumn();

                    if (!is_null($newFileName) && $oldFileName && file_exists('uploads/' . $oldFileName)) {
                        unlink('uploads/' . $oldFileName); // Remove the old file
                    }

                    // Prepare the SQL statement with placeholders


                    $stmt = $pdo->prepare("UPDATE items SET Available=:available, itemPhoto=:itemPhoto, autoProceed=:autoProceed, CategoryID = :catId, regularPrice=:regularPrice,goldPrice=:goldPrice, platinumPrice=:platinumPrice  WHERE ItemID=:itemId");


                    // Bind parameters

                    $stmt->bindParam(':itemPhoto', $newFileName); // Store the file name in the database
                    $stmt->bindParam(':available', $available);
                    $stmt->bindParam(':autoProceed', $autoProceed);
                    $stmt->bindParam(':catId', $catId);
                    $stmt->bindParam(':regularPrice', $regularPrice);
                    $stmt->bindParam(':goldPrice', $goldPrice);
                    $stmt->bindParam(':platinumPrice', $platinumPrice);
                    $stmt->bindParam(':itemId', $itemId);

                    $paramStmt = $pdo->prepare("UPDATE itemparameters SET ParameterName= :param WHERE ItemID = :itemId");
                    $paramStmt->bindParam(':param', $param);
                    $paramStmt->bindParam(':itemId', $itemId);


                    // Execute the statement
                    if ($stmt->execute() && $paramStmt->execute()) {
                        $result = json_encode(array('success' => true, "msg" => "item updated successfully!"));
                    } else {
                        $result = json_encode(array('success' => false, 'msg' => 'Error updating item'));
                    }

                } else {
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            } else {

                // Prepare the SQL statement with placeholders

                $stmt = $pdo->prepare("UPDATE items SET Available= :available, autoProceed=:autoProceed, CategoryID = :catId, regularPrice=:regularPrice,goldPrice=:goldPrice, platinumPrice=:platinumPrice WHERE ItemID=:itemId");


                // Bind parameters

                $stmt->bindParam(':autoProceed', $autoProceed);
                $stmt->bindParam(':available', $available);
                $stmt->bindParam(':catId', $catId);
                $stmt->bindParam(':regularPrice', $regularPrice);
                $stmt->bindParam(':goldPrice', $goldPrice);
                $stmt->bindParam(':platinumPrice', $platinumPrice);
                $stmt->bindParam(':itemId', $itemId);

                $paramStmt = $pdo->prepare("UPDATE itemparameters SET ParameterName= :param WHERE ItemID = :itemId");
                $paramStmt->bindParam(':param', $param);
                $paramStmt->bindParam(':itemId', $itemId);
                // Execute the statement
                if ($stmt->execute() && $paramStmt->execute()) {
                    $result = json_encode(array('success' => true, "msg" => "item updated successfully!"));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error updating item'));
                }

            }
            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($_POST["method"] == "edit-group") {
        try {


            $groupId = $_POST["groupId"];
            $groupName = $_POST["groupName"];


            $newFileName = null; // Initialize the new file name

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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

                    // Check if an old file exists and remove it
                    $stmt = $pdo->prepare("SELECT groupPhoto FROM groups WHERE groupId = :groupId");
                    $stmt->bindParam(':groupId', $groupId);
                    $stmt->execute();
                    $oldFileName = $stmt->fetchColumn();

                    if (!is_null($newFileName) && $oldFileName && file_exists('uploads/' . $oldFileName)) {
                        unlink('uploads/' . $oldFileName); // Remove the old file
                    }

                    // Prepare the SQL statement with placeholders

                    $stmt = $pdo->prepare("UPDATE groups SET groupPhoto=:groupPhoto, groupName=:groupName WHERE groupId=:groupId");

                    // Bind parameters

                    $stmt->bindParam(':groupPhoto', $newFileName); // Store the file name in the database
                    $stmt->bindParam(':groupId', $groupId);
                    $stmt->bindParam(':groupName', $groupName);

                    // Execute the statement
                    if ($stmt->execute()) {
                        $result = json_encode(array('success' => true));
                    } else {
                        $result = json_encode(array('success' => false, 'msg' => 'Error updating category'));
                    }

                } else {
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            } else {
                // Prepare the SQL statement with placeholders

                $stmt = $pdo->prepare("UPDATE groups SET  groupName=:groupName WHERE groupId=:groupId");

                // Bind parameters

                $stmt->bindParam(':groupName', $groupName);
                $stmt->bindParam(':groupId', $groupId);


                // Execute the statement
                if ($stmt->execute()) {
                    $result = json_encode(array('success' => true));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error updating category'));
                }

            }


            echo $result;

        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    if ($_POST["method"] == "edit-cat") {
        try {

            // Define the item name, item text, item price, and subcategory ID to be inserted
            // $catName = $_POST["catName"];
            // $catNameAr = $_POST["catNameAr"];
            $catId = $_POST["catId"];
            $catName = $_POST["catName"];
            $groupId = $_POST["groupId"];
            $autoProceed = $_POST["autoProceed"];


            $newFileName = null; // Initialize the new file name

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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

                    // Check if an old file exists and remove it
                    $stmt = $pdo->prepare("SELECT catPhoto FROM categories WHERE CategoryID = :catId");
                    $stmt->bindParam(':catId', $catId);
                    $stmt->execute();
                    $oldFileName = $stmt->fetchColumn();

                    if (!is_null($newFileName) && $oldFileName && file_exists('uploads/' . $oldFileName)) {
                        unlink('uploads/' . $oldFileName); // Remove the old file
                    }

                    // Prepare the SQL statement with placeholders

                    $stmt = $pdo->prepare("UPDATE categories SET catPhoto=:catPhoto, CategoryName=:CategoryName, groupId=:groupId, autoProceed = :autoProceed WHERE CategoryID=:catId");

                    // Bind parameters

                    $stmt->bindParam(':catPhoto', $newFileName); // Store the file name in the database
                    $stmt->bindParam(':catId', $catId);
                    $stmt->bindParam(':groupId', $groupId);
                    $stmt->bindParam(':autoProceed', $autoProceed);
                    $stmt->bindParam(':CategoryName', $catName);

                    // Execute the statement
                    if ($stmt->execute()) {
                        $result = json_encode(array('success' => true));
                    } else {
                        $result = json_encode(array('success' => false, 'msg' => 'Error updating category'));
                    }

                } else {
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            } else {
                // Prepare the SQL statement with placeholders

                $stmt = $pdo->prepare("UPDATE categories SET  CategoryName=:CategoryName, groupId=:groupId, autoProceed = :autoProceed WHERE CategoryID=:catId");

                // Bind parameters

                $stmt->bindParam(':CategoryName', $catName);
                $stmt->bindParam(':catId', $catId);
                $stmt->bindParam(':groupId', $groupId);
                $stmt->bindParam(':autoProceed', $autoProceed);

                $itemsStmt = $pdo->prepare("UPDATE items set autoProceed = :autoProceed WHERE CategoryID=:catId");
                $itemsStmt->bindParam(':catId', $catId);
                $itemsStmt->bindParam(':autoProceed', $autoProceed);

                // Execute the statement
                if ($stmt->execute() && $itemsStmt->execute()) {
                    $result = json_encode(array('success' => true));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error updating category'));
                }

            }



            echo $result;

        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    if ($_POST["method"] == "edit-sub-cat") {
        try {

            // Define the category ID and name to be updated
            $subCatId = $_POST["subCatId"];
            $subCatName = $_POST["subCatName"];
            $subCatNameAr = $_POST["subCatNameAr"];

            // Prepare the SQL statement with placeholders
            //   $stmt = $pdo->prepare("UPDATE subcategory SET subName = :subCatName, subNameAr = :subCatNameAr WHERE subId = :subCatId");

            //   // Bind parameters
            //   $stmt->bindParam(':subCatName', $subCatName);
            //   $stmt->bindParam(':subCatNameAr', $subCatNameAr);
            //   $stmt->bindParam(':subCatId', $subCatId);

            $newFileName = null; // Initialize the new file name

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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

                    // Check if an old file exists and remove it
                    $stmt = $pdo->prepare("SELECT subPhoto FROM subcategory WHERE subId = :subId");
                    $stmt->bindParam(':subId', $subCatId);
                    $stmt->execute();
                    $oldFileName = $stmt->fetchColumn();

                    if (!is_null($newFileName) && $oldFileName && file_exists('uploads/' . $oldFileName)) {
                        unlink('uploads/' . $oldFileName); // Remove the old file
                    }

                    // Prepare the SQL statement with placeholders

                    $stmt = $pdo->prepare("UPDATE subcategory SET subName=:subName, subNameAr=:subNameAr, subPhoto=:subPhoto WHERE subId=:subId");

                    // Bind parameters
                    $stmt->bindParam(':subName', $subCatName);
                    $stmt->bindParam(':subNameAr', $subCatNameAr);
                    $stmt->bindParam(':subPhoto', $newFileName); // Store the file name in the database
                    $stmt->bindParam(':subId', $subCatId);

                    // Execute the statement
                    if ($stmt->execute()) {
                        $result = json_encode(array('success' => true));
                    } else {
                        $result = json_encode(array('success' => false, 'msg' => 'Error updating category'));
                    }

                } else {
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            } else {
                // Prepare the SQL statement with placeholders

                $stmt = $pdo->prepare("UPDATE subcategory SET subName=:subName, subNameAr=:subNameAr WHERE subId=:subId");

                // Bind parameters
                $stmt->bindParam(':subName', $subCatName);
                $stmt->bindParam(':subNameAr', $subCatNameAr);
                $stmt->bindParam(':subId', $subCatId);

                // Execute the statement
                if ($stmt->execute()) {
                    $result = json_encode(array('success' => true));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error updating sub category'));
                }

            }



            echo $result;

        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($_POST["method"] == "edit-cat-photo") {
        try {


            $catId = $_POST["catId"];

            $newFileName = null; // Initialize the new file name

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            }

            // Check if an old file exists and remove it
            $stmt = $pdo->prepare("SELECT catPhoto FROM categories WHERE catId = :catId");
            $stmt->bindParam(':catId', $catId);
            $stmt->execute();
            $oldFileName = $stmt->fetchColumn();

            if (!is_null($newFileName) && $oldFileName && file_exists('uploads/' . $oldFileName)) {
                unlink('uploads/' . $oldFileName); // Remove the old file
            }

            // Prepare the SQL statement with placeholders

            $stmt = $pdo->prepare("UPDATE categories SET catPhoto=:catPhoto WHERE catId=:catId");

            // Bind parameters

            $stmt->bindParam(':catPhoto', $newFileName); // Store the file name in the database
            $stmt->bindParam(':catId', $catId);

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error updating item'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    if ($_POST["method"] == "edit-sub-cat-photo") {
        try {


            $subId = $_POST["subCatId"];

            $newFileName = null; // Initialize the new file name

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
            }

            // Check if an old file exists and remove it
            $stmt = $pdo->prepare("SELECT subPhoto FROM subcategory WHERE subId = :subId");
            $stmt->bindParam(':subId', $subId);
            $stmt->execute();
            $oldFileName = $stmt->fetchColumn();

            if (!is_null($newFileName) && $oldFileName && file_exists('uploads/' . $oldFileName)) {
                unlink('uploads/' . $oldFileName); // Remove the old file
            }

            // Prepare the SQL statement with placeholders

            $stmt = $pdo->prepare("UPDATE subcategory SET subPhoto=:subPhoto WHERE subId=:subId");

            // Bind parameters

            $stmt->bindParam(':subPhoto', $newFileName); // Store the file name in the database
            $stmt->bindParam(':subId', $subId);

            // Execute the statement
            if ($stmt->execute()) {
                $result = json_encode(array('success' => true));
            } else {
                $result = json_encode(array('success' => false, 'msg' => 'Error updating item'));
            }

            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($_POST["method"] == "edit-offer-photo1") {
        try {




            $newFileName = null; // Initialize the new file name

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
                // Check if an old file exists and remove it

                $stmt = $pdo->prepare("SELECT homePhoto FROM home");

                $stmt->execute();
                $oldFileName = $stmt->fetchColumn();

                if (!is_null($newFileName) && $oldFileName && file_exists('uploads/' . $oldFileName)) {
                    unlink('uploads/' . $oldFileName); // Remove the old file
                }

                // Prepare the SQL statement with placeholders

                $stmt = $pdo->prepare("UPDATE home SET homePhoto=:homePhoto");

                // Bind parameters

                $stmt->bindParam(':homePhoto', $newFileName); // Store the file name in the database


                // Execute the statement
                if ($stmt->execute()) {
                    $result = json_encode(array('success' => true));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error updating item'));
                }


            }



            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    if ($_POST["method"] == "edit-offer-photo2") {
        try {




            $newFileName = null; // Initialize the new file name

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
                // Check if an old file exists and remove it

                $stmt = $pdo->prepare("SELECT homePhoto1 FROM home");

                $stmt->execute();
                $oldFileName = $stmt->fetchColumn();

                if (!is_null($newFileName) && $oldFileName && file_exists('uploads/' . $oldFileName)) {
                    unlink('uploads/' . $oldFileName); // Remove the old file
                }

                // Prepare the SQL statement with placeholders

                $stmt = $pdo->prepare("UPDATE home SET homePhoto1=:homePhoto1");

                // Bind parameters

                $stmt->bindParam(':homePhoto1', $newFileName); // Store the file name in the database


                // Execute the statement
                if ($stmt->execute()) {
                    $result = json_encode(array('success' => true));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error updating item'));
                }


            }



            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


    if ($_POST["method"] == "edit-offer-photo3") {
        try {




            $newFileName = null; // Initialize the new file name

            // Handle file upload
            if (isset($_FILES['fileSource'])) {
                $file = $_FILES['fileSource'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];

                // Check if there was no file upload error
                if ($fileError === UPLOAD_ERR_OK) {
                    // Define the directory where you want to save the uploaded files
                    $uniqueId = uniqid(); // Generate a unique ID
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION); // Get the file extension
                    $newFileName = $uniqueId . '.' . $extension; // Append unique ID to the file name

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
                    // Handle the case where file upload had an error
                    $result = json_encode(array('success' => false, 'msg' => 'File upload error: ' . $fileError));
                    echo $result;
                    exit;
                }
                // Check if an old file exists and remove it

                $stmt = $pdo->prepare("SELECT homePhoto2 FROM home");

                $stmt->execute();
                $oldFileName = $stmt->fetchColumn();

                if (!is_null($newFileName) && $oldFileName && file_exists('uploads/' . $oldFileName)) {
                    unlink('uploads/' . $oldFileName); // Remove the old file
                }

                // Prepare the SQL statement with placeholders

                $stmt = $pdo->prepare("UPDATE home SET homePhoto2=:homePhoto2");

                // Bind parameters

                $stmt->bindParam(':homePhoto2', $newFileName); // Store the file name in the database


                // Execute the statement
                if ($stmt->execute()) {
                    $result = json_encode(array('success' => true));
                } else {
                    $result = json_encode(array('success' => false, 'msg' => 'Error updating item'));
                }


            }



            echo $result;
        } catch (PDOException $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }


}