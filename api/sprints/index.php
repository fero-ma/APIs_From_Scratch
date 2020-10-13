<?php 
  // Headers
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');

  $request = $_SERVER["REQUEST_METHOD"];
  
  if($request=="OPTIONS")
  {
        http_response_code(200);
  }
  else
  {
        include_once '../../models/hash.php';

        $data = json_decode(file_get_contents("php://input"));

        if(isset($_REQUEST['key']))
        {
            $key=$_REQUEST['key'];
        }
        elseif(isset($data->key))
        {
            $key=$data->key;
        }
        else
        {
            http_response_code(400);
            echo json_encode(array('error'=>"UNAUTHORIZED",
                                    'message'=>"Missing Key"));
            die();
        }

        if(!hash_checker($key))
        {
            http_response_code(403);
            echo json_encode(array('error'=>"UNAUTHORIZED",
                                  'message'=>"Invalid Key"));
            die();
        }
        else
        {
            http_response_code(200);
        }

        include_once '../../config/Database.php';
        include_once '../../models/Sprint.php';
        include_once '../../models/User.php';

        // Instantiate DB & connect
        $database = new Database();
        $db = $database->connect();

        $user = new User($db);
        
        $header=getallheaders();
        if(!isset($header['Authorization']))
        {
            http_response_code(400);
            echo json_encode(array('error'=>"UNAUTHORIZED",
                                    'message'=>"Missing Access Token"));
            die();   
        }
        $auth = $header['Authorization'];
        $auth = str_replace("Bearer ","",$auth);

        //New Issue Object
        $sprint = new Sprint($db);

        $e=$user->validateToken($auth);

        if($e>-1)
        {     
                $result=$user->getTokenDetails($e);
                $result = $result->fetch(PDO::FETCH_ASSOC);
                $user_id=$result["user_id"];

                if($request=="GET")
                {
                
                        if(isset($_REQUEST["showAll"]) && $_REQUEST["showAll"]==True)
                        {
                            $result = $sprint->readSprints();
                        }
                        elseif(isset($_REQUEST["sprint_id"]))
                        {
                                if(!filter_var($_REQUEST["sprint_id"],FILTER_VALIDATE_INT))
                            {
                                http_response_code(400);
                                echo json_encode(
                                array('error' => 'READ_FAILED',
                                      'message'=> 'Check parameters')
                                );
                                die();
                            }
                            $result = $sprint->readSingle($_REQUEST);

                        }
                        else
                        {
                            $result = $sprint->currentSprint($_REQUEST);
                        }
                    
                            $num = $result->rowCount();

                            if($num!=0) 
                            {
                                $sprints_arr = array();

                                while($row = $result->fetch(PDO::FETCH_ASSOC))
                                {
                                    extract($row);
                                    $sprint_item = array(
                                    'sprint_id' => html_entity_decode($sprint_id),
                                    'startdate' => html_entity_decode($startdate),
                                    'enddate' => html_entity_decode($enddate),
                                    'duration' => html_entity_decode($duration),
                                    'createdby' => html_entity_decode($createdby),
                                    );
                            
                                
                                    array_push($sprints_arr, $sprint_item);

                                }
                                echo json_encode($sprints_arr);
                            }
                            else
                            {       
                                    http_response_code(204);
                                    echo json_encode(
                                        array('message' => 'No sprints')
                                    );
                            }
                }
                elseif($request=="POST")
                {
                    //$data = json_decode(file_get_contents("php://input"));

                    if(isset($data->enddate))
                    {
                        $enddate  = explode('-', $data->enddate);

                        if(checkdate($enddate[1],$enddate[2],$enddate[0]))
                        {
                            $startdate  = date('Y-m-d');
                            $duration=(strtotime($data->enddate)-strtotime($startdate))/86400;
            
                            if($duration>0)
                            {   
                                $sprint->createSprint($startdate,$data->enddate,$duration,$user_id);
                                http_response_code(201);
                                $result=$sprint->currentSprint();
                                $result=$result->fetch(PDO::FETCH_ASSOC);

                                $sprint_item = array(
                                    'sprint_id' => $result["sprint_id"],
                                    'startdate' => $result["startdate"],
                                    'enddate' => $result["enddate"],
                                    'duration' => $result["duration"],
                                    'createdby' => $result["createdby"]
                                    );

                                echo json_encode(
                                    array('message' => 'Created sprint!',
                                          'sprint'=> $sprint_item)
                                    );
                            }
                            else
                            {   
                                http_response_code(400);
                                echo json_encode(
                                    array('error' => 'CREATE_FAILED',
                                          'message'=>'Enter a later enddate')
                                    );
                            }
                        }
                        else
                        {
                            http_response_code(400);
                            echo json_encode(
                                array('error' => 'CREATE_FAILED',
                                      'message'=>'Enter a valid enddate')
                                );
                        }
                    }
                    else
                    {
                        http_response_code(406);
                        echo json_encode(
                            array('error' => 'CREATE_FAILED',
                                  'message'=>'Missing params')
                            );
                    }
                }
        }
        else
        {
            switch($e)
            {
                case "-1":
                        http_response_code(401);   
                        echo json_encode(
                        array('error' => 'TOKEN_EXPIRED')
                        );
                        break;
                case "-2":
                        http_response_code(401);   
                        echo json_encode(
                        array('error' => 'INVALID_ACCESS_TOKEN')
                        );
                        break;

            }
        }

        $user->close();
        $sprint->close();
  }
?>