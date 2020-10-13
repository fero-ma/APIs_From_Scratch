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
        include_once '../../models/Team.php';
        include_once '../../models/User_Details.php';
        include_once '../../models/User.php';
        include_once '../../models/hash.php';

        $database = new Database();
        $db = $database->connect();

        $post = new Team($db);
        $user_details = new User_Details($db);
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
            $user_details->user_id = $result["user_id"];

            if($request == "GET")
            {
                if(isset($_GET["key"]))
                {
                    $hash = $_GET["key"];
                    if(hash_checker($hash))
                    {
                            $res = $post->teams();
                            if($res->rowCount() > 0)
                            {
                                $post_ar = array();

                                while($row = $res->fetch(PDO::FETCH_ASSOC))
                                {
                                    extract($row);
                                    $post_arr = array(
                                        "team_id" => $team_id,
                                        "teamname" => $teamname
                                    );
                                    http_response_code(200);
                                    array_push($post_ar, $post_arr);
                                }
                                echo json_encode($post_ar);
                            }
                            else
                            {
                                $output["Error"] = "REQUEST_FAILED";
                                $output["Message"] = "NO_TEAMS_EXIST";
                                http_response_code(400);
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
            elseif($request == "POST")
            {
                    
                $data = json_decode(file_get_contents("php://input"));
                if(isset($data->key))
                {
                    $hash = $data->key;
                    if(hash_checker($hash))
                    {
                        $row = $user_details->readSingle();
                        $row = $row->fetch(PDO::FETCH_ASSOC); 
                        $user_details->role = $row["role"];
                        if($user_details->role == "admin")
                        {
                            if(isset($data->teamname))
                            {
                                if(filter_var($data->teamname,FILTER_SANITIZE_SPECIAL_CHARS))
                                {
                                    $post->teamname = $data->teamname;

                                    $team_id = $post->create_team();
                                    $post->team_id = $team_id;
                                    $res = $post->readSingleTeam();
                                    if($res->rowCount() > 0)
                                    {
                                        $row = $res->fetch(PDO::FETCH_ASSOC);
                                        $details = array(
                                            "team_id" => $row["team_id"],
                                            "teamname" => $row["teamname"]
                                        );
                                        http_response_code(200);
                                        echo json_encode($details);
                                    }
                                    else
                                    {
                                        $output["Error"] = "REQUEST_FAILED";
                                        $output["Message"] = "TEAM_CANNOT_BE_CREATED";
                                        http_response_code(400);
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
                            else
                            {
                                $output["Error"] = "REQUEST_FAILED";
                                $output["Message"] = "MISSING_PARAMS";
                                http_response_code(400);
                                echo json_encode($output);
                            }
                            
                        }
                        else
                        {
                            $output["Error"] = "REQUEST_FAILED";
                            $output["Message"] = "ACCESS_DENIED";
                            http_response_code(401);
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
            $post->close();
            $user->close();
            $user_details->close();
        }
        else{
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

    
