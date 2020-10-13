<?php
    //HEADERS
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    header('Access-Control-Allow-Methods: POST, OPTIONS, PUT, DELETE, GET');
    header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');

    $request = $_SERVER["REQUEST_METHOD"];

    if($request == "OPTIONS")
    {
        http_response_code(200);
    }
    else
    {
        include_once '../../config/Database.php';
        include_once '../../models/User_Details.php';
        include_once '../../models/User.php';
        include_once '../../models/hash.php';

        $database = new Database();
        $db = $database->connect();

        $post = new User_Details($db);
        $user = new User($db);
        
        $header = getallheaders();
        if(isset($header['Authorization']))
        {
            $auth = $header['Authorization'];
        }
        else {
            http_response_code(406);
            echo json_encode(array("error" => "INVALID_ACCESS_TOKEN"));
            die();
        }
        $auth = str_replace("Bearer ","",$auth);
        $request = $_SERVER["REQUEST_METHOD"];
        $e = $user->validateToken($auth);

        if($e>-1)
        {
            $result = $user->getTokenDetails($e);
            $result = $result->fetch(PDO::FETCH_ASSOC);
            $post->user_id = $result["user_id"];

            if($request == "GET")
            {
                $result = $post->readSingle();
                $row = $result->fetch(PDO::FETCH_ASSOC);
                $post->role = $row["role"];

                if($post->role == "admin")
                {
                    if(isset($_GET["showAll"]) && isset($_GET["key"]))
                    {
                        $hash = $_GET["key"];
                        if(hash_checker($hash))
                        {
                            if($_GET["showAll"] == "True")
                            {
                                $res = $post->getUserIdName();
                                if($res->rowCount() > 0)
                                {
                                    $post_arr = array();
                                    while($row = $res->fetch(PDO::FETCH_ASSOC))
                                    {
                                        extract($row);
                                        $post_item = array(
                                            "user_id" => $user_id,
                                            "fullname" => $fullname,
                                            "team_id" => $team_id
                                        );
                                        array_push($post_arr, $post_item);
                                    }
                                    http_response_code(200);
                                    echo json_encode($post_arr);
                                }
                                else
                                {
                                    $output["Error"] = "REQUEST_FAILED";
                                    $output["Message"] = "NO_USER_EXIST";
                                    http_response_code(400);
                                    echo json_encode($output);
                                }
                            }
                            else
                            {
                                $output["Error"] = "REQUEST_FAILED";
                                $output["Message"] = "CHECK_PARAMS";
                                http_response_code(400);
                                echo json_encode($output);
                            }
                        }
                        else{
                            $output["Error"] = "INVALID_ACCESS";
                            $output["Message"] = "KEY_NOT_VALID";
                            http_response_code(406);
                            echo json_encode($output);
                        }

                    }
                    else
                    {
                        if(isset($_GET["team_id"], ($_GET["key"])))
                        {
                            $hash = $_GET["key"];
                            if(hash_checker($hash))
                            {
                                $post->team_id = $_GET["team_id"];

                                $result = $post->member_details();
                    
                                $count = $result->rowCount();
                    
                                if($count > 0)
                                {
                                    //POST ARRAY
                                    $posts_ar = array();
                    
                                    //ITERATE THROUGH THE FETCHED VALUES
                                    while($row  = $result->fetch(PDO::FETCH_ASSOC))
                                    {
                                        extract($row);
                    
                                        $post_item = array(
                                            'user_id' => $user_id,
                                            'fullname' => $fullname,
                                            'nationality' => $nationality,
                                            'team_id' => $team_id,
                                            'profile picture' => $image,
                                            'role' => $role
                                        );
                    
                                        //PUSH THE VALUES IN '$post_item' to '$posts_arr'
                                        array_push($posts_ar, $post_item);
                                    }
                                    http_response_code(200);
                                    echo json_encode($posts_ar);
                                }
                                else
                                {
                                    $output["Error"] = "REQUEST_FAILED";
                                    $output["Message"] = "NO_TEAM_MEMBERS_EXIST";
                                    http_response_code(204);
                                    echo json_encode($output);
                                }
                            }
                            else{
                                $output["Error"] = "INVALID_ACCESS";
                                $output["Message"] = "KEY_NOT_VALID";
                                http_response_code(406);
                                echo json_encode($output);
                            }
                        }
                        else
                        {
                            //$post->user_id = isset($_GET["user_id"]) ? $_GET["user_id"] : die();
                            if(isset($_GET["key"]))
                            {
                                $hash = $_GET["key"];
                                if(hash_checker($hash))
                                {
                                    $result = $post->readSingle();
                                    if($result->rowCount() > 0)
                                    {
                                        $row = $result->fetch(PDO::FETCH_ASSOC);
                                    
                                        $post->user_id = $row["user_id"];
                                        $post->fullname = $row["fullname"];
                                        $post->nationality = $row["nationality"];
                                        $post->team_id = $row["team_id"];
                                        $post->image = $row['image'];
                                        $post->role = $row['role'];
                                        $post_arr = array(
                                            "user_id" => $post->user_id,
                                            "fullname" => $post->fullname,
                                            "nationality" => $post->nationality,
                                            "team_id" => $post->team_id,
                                            "profile picture" =>$post->image,
                                            'role' => $post->role
                                        );
                                        http_response_code(200);
                                        echo json_encode($post_arr);
                                    }
                                    else
                                    {
                                        $output["Error"] = "SERVER_ERROR";
                                        http_response_code(504);
                                        echo json_encode($output);
                                    }
                                }
                                else
                                {
                                    $output["Error"] = "INVALID_ACCESS";
                                    $output["Message"] = "KEY_NOT_VALID";
                                    http_response_code(406);
                                    echo json_encode($output);
                                }
                            }
                            else
                            {
                                $output["Error"] = "REQUEST_FAILED";
                                $output["Message"] = "MISSING_PARAMS";
                                http_response_code(400);
                                echo json_encode($output);
                            }
                        }
                    }
                }
                else
                {
                    if(isset($_GET["team_id"], ($_GET["key"])))
                    {
                        $hash = $_GET["key"];
                        if(hash_checker($hash))
                        {
                            $post->team_id = $_GET["team_id"];

                            $result = $post->member_details();
                
                            $count = $result->rowCount();
                
                            if($count > 0)
                            {
                                //POST ARRAY
                                $posts_ar = array();
                
                                //ITERATE THROUGH THE FETCHED VALUES
                                while($row  = $result->fetch(PDO::FETCH_ASSOC))
                                {
                                    extract($row);
                
                                    $post_item = array(
                                        'user_id' => $user_id,
                                        'fullname' => $fullname,
                                        'nationality' => $nationality,
                                        'team_id' => $team_id,
                                        'profile picture' => $image,
                                        'role' => $role
                                    );
                
                                    //PUSH THE VALUES IN '$post_item' to '$posts_arr'
                                    array_push($posts_ar, $post_item);
                                }
                                http_response_code(200);
                                echo json_encode($posts_ar);
                            }
                            else
                            {
                                $output["Error"] = "REQUEST_FAILED";
                                $output["Message"] = "NO_TEAM_MEMBERS_EXIST";
                                http_response_code(204);
                                echo json_encode($output);
                            }
                        }
                        else{
                            $output["Error"] = "INVALID_ACCESS";
                            $output["Message"] = "KEY_NOT_VALID";
                            http_response_code(406);
                            echo json_encode($output);
                        }
                    }
                    elseif(isset($_GET["showAll"]) && isset($_GET["key"]))
                    {
                        $hash = $_GET["key"];
                        if(hash_checker($hash))
                        {
                            if($_GET["showAll"] == "True")
                            {
                                $res = $post->getUserIdName();
                                if($res->rowCount() > 0)
                                {
                                    $post_arr = array();
                                    while($row = $res->fetch(PDO::FETCH_ASSOC))
                                    {
                                        extract($row);
                                        $post_item = array(
                                            "user_id" => $user_id,
                                            "fullname" => $fullname,
                                            "team_id" => $team_id
                                        );
                                        array_push($post_arr, $post_item);
                                    }
                                    http_response_code(200);
                                    echo json_encode($post_arr);
                                }
                                else
                                {
                                    $output["Error"] = "REQUEST_FAILED";
                                    $output["Message"] = "NO_USER_EXIST";
                                    http_response_code(400);
                                    echo json_encode($output);
                                }
                            }
                            else
                            {
                                $output["Error"] = "REQUEST_FAILED";
                                $output["Message"] = "CHECK_PARAMS";
                                http_response_code(400);
                                echo json_encode($output);
                            }
                        }
                        else{
                            $output["Error"] = "INVALID_ACCESS";
                            $output["Message"] = "KEY_NOT_VALID";
                            http_response_code(406);
                            echo json_encode($output);
                        }

                    }
                    else
                    {
                        //$post->user_id = isset($_GET["user_id"]) ? $_GET["user_id"] : die();
                        if(isset($_GET["key"]))
                        {
                            $hash = $_GET["key"];
                            if(hash_checker($hash))
                            {
                                $result = $post->readSingle();
                                if($result->rowCount() > 0)
                                {
                                    $row = $result->fetch(PDO::FETCH_ASSOC);
                                
                                    $post->user_id = $row["user_id"];
                                    $post->fullname = $row["fullname"];
                                    $post->nationality = $row["nationality"];
                                    $post->team_id = $row["team_id"];
                                    $post->image = $row['image'];
                                    $post->role = $row['role'];
                                    $post_arr = array(
                                        "user_id" => $post->user_id,
                                        "fullname" => $post->fullname,
                                        "nationality" => $post->nationality,
                                        "team_id" => $post->team_id,
                                        "profile picture" =>$post->image,
                                        'role' => $post->role
                                    );
                                    http_response_code(200);
                                    echo json_encode($post_arr);
                                }
                                else
                                {
                                    $output["Error"] = "SERVER_ERROR";
                                    http_response_code(504);
                                    echo json_encode($output);
                                }
                            }
                            else
                            {
                                $output["Error"] = "INVALID_ACCESS";
                                $output["Message"] = "KEY_NOT_VALID";
                                http_response_code(406);
                                echo json_encode($output);
                            }
                        }
                        else
                        {
                            $output["Error"] = "REQUEST_FAILED";
                            $output["Message"] = "MISSING_PARAMS";
                            http_response_code(400);
                            echo json_encode($output);
                        }
                    }
                }
            }
        
                elseif($request == "POST")
                {
                    $result = $post->readSingle();
                    $row = $result->fetch(PDO::FETCH_ASSOC);
                    $post->role = $row["role"];
                    
                    // CREATE USER
                    if($post->role=="admin")
                    {
                        $data = json_decode(file_get_contents("php://input"));
                        if(isset($data->key)&&isset($data->username)&&isset($data->password)&&isset($data->fullname)&&isset($data->nationality)&&isset($data->team_id)&&isset($data->role))
                        {
                            if(filter_var($data->username,FILTER_SANITIZE_SPECIAL_CHARS)
                            &&filter_var($data->password,FILTER_SANITIZE_SPECIAL_CHARS)&&filter_var($data->fullname,FILTER_SANITIZE_SPECIAL_CHARS)
                            &&filter_var($data->nationality,FILTER_SANITIZE_SPECIAL_CHARS)&&filter_var($data->team_id,FILTER_VALIDATE_INT)
                            &&filter_var($data->role,FILTER_SANITIZE_SPECIAL_CHARS))
                            {
                                $hash = $data->key;
                                if(hash_checker($hash))
                                {       
                                        $post->username = $data->username;

                                        $test=$user->readSingle($post->username);
                                        if($test->rowCount()>0)
                                            {
                                                $output["Error"] = "USER_EXIST";
                                                http_response_code(400);
                                                echo json_encode($output);
                                                die();
                                            }
                                        else
                                        {
                                            $post->password = $user->encrypt($data->password);
                                            $post->fullname = $data->fullname;
                                            $post->nationality = $data->nationality;
                                            $post->team_id = $data->team_id;
                                            $post->role = $data->role;
                                            $userid = $post->create_user_and_details();
                                            $post->user_id = $userid;
                                            $res=$post->readSingle(); //admin
                                            $count = $res->rowCount();
                                            
                                            if($count>0)
                                            {
                                                $row = $res->fetch(PDO::FETCH_ASSOC);
                                                $post->user_id = $row["user_id"];
                                                        // $post->username = $row["username"];
                                                        // $post->password = $row["password"];
                                                $post->fullname = $row["fullname"];
                                                $post->nationality = $row["nationality"];
                                                $post->team_id = $row["team_id"];
                                                $post->image = $row['image'];
                                                $post->role = $row['role'];
                                                $post_arr = array(
                                                    "message" => "INSERT_SUCCESS",
                                                    "user_id" => $post->user_id,
                                                    "fullname" => $post->fullname,
                                                    "nationality" => $post->nationality,
                                                    "team_id" => $post->team_id,
                                                    "profile picture" =>$post->image,
                                                    'role' => $post->role
                                                );
                                                http_response_code(200);
                                                echo json_encode($post_arr);
                                            }
                                            else
                                            {
                                                $output['message'] = 'SERVER_ERROR';
                                                http_response_code(504);
                                                echo json_encode($output);
                                            }
                                        }
                                }
                                else
                                {
                                    $output["Error"] = "INVALID_ACCESS";
                                    $output["Message"] = "KEY_NOT_VALID";
                                    http_response_code(406);
                                    echo json_encode($output);
                                }
                            }
                            else
                            {
                                $output["Error"] = "INSERT_FAILED";
                                $output["Message"] = "CHECK_PARAMS";
                                http_response_code(400);
                                echo json_encode($output);
                            }
                        }  
                        
                        // CHANGE PHOTO
                        elseif(isset($_FILES["file"]) && is_uploaded_file($_FILES['file']['tmp_name']) && $_POST["key"])
                        {
                            $hash = $_POST["key"];
                            if(hash_checker($hash))
                            {
                                $post->user_id = $row["user_id"];
                                $post->change_photo();
                                echo json_encode($post->response);
                            }
                            else
                            {
                                $output['Error'] = 'INVALID_ACCESS';
                                $output["Message"] = "KEY_NOT_VALID";
                                http_response_code(400);
                                echo json_encode($output);
                            }
                        }
                        else
                        {
                            $output["Error"] = "INSERT_FAILED";
                            $output["Message"] = "MISSING_PARAMS";
                            http_response_code(400);
                            echo json_encode($output);
                        }
                        
                    }
                    else
                    {
                        // CHANGE PHOTO
                        if(isset($_FILES["file"]) && is_uploaded_file($_FILES['file']['tmp_name']) && $_POST["key"])
                        {
                            $hash = $_POST["key"];
                            if(hash_checker($hash))
                            {
                                $post->user_id = $row["user_id"];
                                $post->change_photo();
                                echo json_encode($post->response);
                            }
                            else
                            {
                                $output["Error"] = 'INVALID_KEY';
                                $output["Message"] = "KEY_NOT_VALID";
                                http_response_code(406);
                                echo json_encode($output);
                            }
                        }
                        else
                        {
                            $output["Error"] = "UPDATE_FAILED";
                            $output["Message"] = "MISSING_PARAMS";
                            http_response_code(400);
                            echo json_encode($output);
                        }
                    }
                                
                }
                elseif($request == "PUT"){
                    $data = json_decode(file_get_contents("php://input"));
                    if(isset($data->key))
                    {
                        $hash = $data->key;
                        if(hash_checker($hash))
                        {
                            $result = $post->readSingle();
                            $row = $result->fetch(PDO::FETCH_ASSOC);
                            $post->role = $row["role"];
                            // UPDATE USER
                            if($post->role == "admin")
                            {
                                if(isset($data->user_id)&&isset($data->fullname)&&isset($data->nationality)&&isset($data->team_id)&&isset($data->role))
                                {
                                    if( filter_var($data->user_id,FILTER_VALIDATE_INT)&&filter_var($data->fullname,FILTER_SANITIZE_SPECIAL_CHARS)
                                    &&filter_var($data->nationality,FILTER_SANITIZE_SPECIAL_CHARS)&&filter_var($data->team_id,FILTER_VALIDATE_INT)
                                    &&filter_var($data->role,FILTER_SANITIZE_SPECIAL_CHARS))
                                    {
                                        $post->user_id = $data->user_id;
                                        $user_present = $post->readsingle();
                                        if($user_present->rowCount()>0)
                                        {
                                            $post->fullname = $data->fullname;
                                            $post->nationality = $data->nationality;
                                            $post->team_id = $data->team_id;
                                            $post->role = $data->role;
                                            $post->update_user_details();
                                            $res = $post->readSingle();
                                            $count = $res->rowCount();
                                            if($count > 0 )
                                            {
                                                $row = $res->fetch(PDO::FETCH_ASSOC);
                                                $post_arr = array(
                                                    "message" => "UPDATE_SUCCESS",
                                                    "user_id" => $row["user_id"],
                                                    "fullname" => $row["fullname"],
                                                    "nationailty" => $row["nationality"],
                                                    "team_id" => $row["team_id"],
                                                    "role" => $row["role"]
                                                );
                                                http_response_code(200);
                                                echo json_encode($post_arr);
                                            }
                                        }
                                        else
                                        {
                                            $output["Error"] = "UPDATE_FAILED";
                                            $output["Message"] = "USER_ID_INVALID";
                                            http_response_code(400);
                                            echo json_encode($output);
                                        }
                                        
                                    }
                                    else
                                    {
                                        $output["Error"] = "UPDATE_FAILED";
                                        $output["Message"] = "CHECK_PARAMS";
                                        http_response_code(400);
                                        echo json_encode($output);
                                    }
                                }
                                elseif(isset($data->pass) && isset($data->retype_pass))
                                {
                                    $post->user_id = $row["user_id"];
                                    
                                    if(filter_var($data->pass, FILTER_SANITIZE_SPECIAL_CHARS) && filter_var($data->retype_pass, FILTER_SANITIZE_SPECIAL_CHARS))
                                    {
                                        $post->pass = $user->encrypt(filter_var($data->pass, FILTER_SANITIZE_SPECIAL_CHARS));
                                        $post->retype_pass = $user->encrypt(filter_var($data->retype_pass, FILTER_SANITIZE_SPECIAL_CHARS));
                                        $post->change_password();
                                        echo json_encode($post->response);
                                    }
                                    else
                                    {
                                        $output["Error"] = "UPDATE_FAILED";
                                        $output["Message"] = "CHECK_PARAMS";
                                        http_response_code(400);
                                        echo json_encode($output);
                                    }
                                    
                                }
                                else
                                {
                                    $output["Error"] = "UPDATE_FAILED";
                                    $output["Message"] = "MISSING_PARAMS";
                                    http_response_code(400);
                                    echo json_encode($output);
                                }
                                
                            }
                            else
                            {
                                // CHANGE PASSWORD
                                $post->user_id = $row["user_id"];
                                if(isset($data->pass) && isset($data->retype_pass))
                                {
                                    if(filter_var($data->pass, FILTER_SANITIZE_SPECIAL_CHARS) && filter_var($data->retype_pass, FILTER_SANITIZE_SPECIAL_CHARS))
                                    {
                                        $post->pass = $user->encrypt(filter_var($data->pass, FILTER_SANITIZE_SPECIAL_CHARS));
                                        $post->retype_pass = $user->encrypt(filter_var($data->retype_pass, FILTER_SANITIZE_SPECIAL_CHARS));
                                        $post->change_password();
                                        echo json_encode($post->response);
                                    }
                                    else
                                    {
                                        $output["Error"] = "UPDATE_FAILED";
                                        $output["Message"] = "CHECK_PARAMS";
                                        http_response_code(400);
                                        echo json_encode($output);
                                    }
                                }
                                else
                                {
                                    $output["Error"] = "UPDATE_FAILED";
                                    $output["Message"] = "MISSING_PARAMS";
                                    http_response_code(400);
                                    echo json_encode($output);
                                }
                                
                            }
                        }
                        else
                        {
                            $output["Error"] = 'INVALID_ACCESS';
                            $output["Message"] = "KEY_NOT_VALID";
                            http_response_code(406);
                            echo json_encode($output);
                        }
                    }
                    else
                    {
                        $output["Error"] = "UPDATE_FAILED";
                        $output["Message"] = "MISSING_PARAMS";
                        http_response_code(400);
                        echo json_encode($output);
                    }
                            
                
                }
                elseif($request == "DELETE")
                {
                    $data = json_decode(file_get_contents("php://input"));
                    if(isset($data->key))
                    {
                        $hash = $data->key;
                        if(hash_checker($hash))
                        {
                            $result = $post->readSingle();
                            $row = $result->fetch(PDO::FETCH_ASSOC);
                            $post->role = $row["role"];
                            if($post->role=="admin")
                            {
                                $data = json_decode(file_get_contents("php://input"));
                                if(isset($data->user_id))
                                {
                                    if(filter_var($data->user_id, FILTER_VALIDATE_INT))
                                    {
                                        $post->user_id = $data->user_id;
                                        $res = $post->readSingle();
                                        if($res->rowCount()>0)
                                        {
                                            echo json_encode($post->delete_user());
                                        }
                                        else
                                        {
                                            $output["Error"] = "DELETE_FAILED";
                                            $output["Message"] = "INVALID_USER_ID";
                                            http_response_code(400);
                                            echo json_encode($output);
                                        }
                                        
                                    }
                                    else
                                    {
                                        $output["Error"] = "DELETE_FAILED";
                                        $output["Message"] = "CHECK_PARAMS";
                                        http_response_code(400);
                                        echo json_encode($output);
                                    }
                                }
                                else
                                {
                                    $output["Error"] = "DELETE_FAILED";
                                    $output["Message"] = "MISSING_PARAMS";
                                    http_response_code(400);
                                    echo json_encode($output);
                                }    
                            }
                            else
                            {
                                $output["Error"] = "DELETE_FAILED";
                                $output["Message"] = "ACCESS_DENIED";
                                http_response_code(401);
                                echo json_encode($output);
                            }
                        }
                        else
                        {
                            $output["Error"] = "IVALID_ACCESS";
                            $output["Message"] = "KEY_NOT_VALID";
                            http_response_code(406);
                            echo json_encode($output);
                        }
                    }
                    else
                    {
                        $output["Error"] = "DELETE_FAILED";
                        $output["Message"] = "MISSING_PARAMS";
                        http_response_code(400);
                        echo json_encode($output);
                    }
                    
                }
            $post->close();
            $user->close();
        }
        else
        {
            switch($e)
            {
                case "-1":
                    http_response_code(401);
                    echo json_encode(
                        array("error" => "TOKEN_EXPIRED")
                    );
                    break;
                case "-2":
                    http_response_code(401);
                    echo json_encode(
                        array("error" => "INVALID_ACCESS_TOKEN")
                    );
                    break;
            }
        }
    }

    

    
    

    