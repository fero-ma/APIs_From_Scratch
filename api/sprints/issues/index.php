<?php 
  // Headers
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');
  header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');

  $request = $_SERVER["REQUEST_METHOD"];
  
  if($request=="OPTIONS")
  {
      http_response_code(200);
  }
  else
  {
        include_once '../../../models/hash.php';
     
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
      
        include_once '../../../config/Database.php';
        include_once '../../../models/Issue.php';
        include_once '../../../models/User.php';

        // Instantiate DB & connect
        $database = new Database();
        $db = $database->connect();
      
        //New Issue Object
        $issue = new Issue($db);
      
        
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
        
        $e = $user->validateToken($auth);

        if($e>-1)
        {
          $result=$user->getTokenDetails($e);
          $result = $result->fetch(PDO::FETCH_ASSOC);
          $user_id=$result["user_id"];
      
          if($request=="GET")
          {
                if(isset($_REQUEST["sprint_id"]))
                {
                  if(filter_var($_REQUEST["sprint_id"],FILTER_VALIDATE_INT))
                  {
                        $result = $issue->readAll($_REQUEST);
      
                        $num = $result->rowCount();
      
                        if($num!=0) 
                        {
                            $issues_arr = array();
      
                            while($row = $result->fetch(PDO::FETCH_ASSOC))
                            {
                              extract($row);
                              $issue_item = array(
                                'issue_id' => html_entity_decode($issue_id),
                                'heading' => html_entity_decode($heading),
                                'reportedby' => html_entity_decode($reportedby),
                                'assignedto' => html_entity_decode($assignedto),
                                'team_id' => html_entity_decode($team_id),
                                'priority' => html_entity_decode($priority),
                                'status' => html_entity_decode($status),
                                'sprint_id' => html_entity_decode($sprint_id),
                                'version' => html_entity_decode($version),
                                'points' => html_entity_decode($points),
                                'date' => html_entity_decode($date),
                                'description' => html_entity_decode($description)
                              );
                        
                            
                              array_push($issues_arr, $issue_item); //Push to "issues"
      
                            }
                            echo json_encode($issues_arr);
                        }
                        else
                        {       
                                http_response_code(204);
                                echo json_encode(
                                  array('message' => 'No issues')
                                );
                        }
                  }
                  else
                  {
                    http_response_code(400);
                            echo json_encode(
                              array('error'=>'READ_FAILED',
                                    'message' => 'Check params')
                            );               
                  }
      
                }
                elseif(isset($_REQUEST["issue_id"]))
                {
                    if(filter_var($_REQUEST["issue_id"],FILTER_VALIDATE_INT))
                    {
                          $result = $issue->readSingle($_REQUEST);
                          $num = $result->rowCount();
              
                          if($num!=0) 
                          {
                              $issues_arr = array();
                            
                              while($row = $result->fetch(PDO::FETCH_ASSOC))
                              {
                                extract($row);
                                $issue_item = array(
                                  'issue_id' => html_entity_decode($issue_id),
                                  'heading' => html_entity_decode($heading),
                                  'reportedby' => html_entity_decode($reportedby),
                                  'assignedto' => html_entity_decode($assignedto),
                                  'team_id' => html_entity_decode($team_id),
                                  'priority' => html_entity_decode($priority),
                                  'status' => html_entity_decode($status),
                                  'sprint_id' => html_entity_decode($sprint_id),
                                  'version' => html_entity_decode($version),
                                  'points' => html_entity_decode($points),
                                  'date' => html_entity_decode($date),
                                  'description' => html_entity_decode($description),
                                  'comments'=> $issue->readComments($_REQUEST)
                                );
                              
                                array_push($issues_arr, $issue_item); //Push to "issues"
              
                                echo json_encode($issues_arr);
                              }
                          }
                          else
                          {       
                                  http_response_code(404);
                                  echo json_encode(
                                    array('error' => 'READ_FAILED',
                                          'message'=> 'No issues found')
                                  );
                          }
                    }
                    else
                    {
                      http_response_code(400);
                      echo json_encode(
                        array('error' => 'READ_FAILED',
                              'message'=>'Check Params')
                      );
                    }
                }
                else
                {
                  http_response_code(400);
                  echo json_encode(
                    array('error' => 'READ_FAILED',
                          'message'=>'Missing Params')
                  );
                }
                
          }
          elseif($request=="POST")
          {
              //$data = json_decode(file_get_contents("php://input"));
              if(isset($data->heading) && isset($data->assignedto) && isset($data->priority) && isset($data->status) && isset($data->sprint_id) && isset($data->version) && isset($data->points) && isset($data->description))
              {
                  $date  = date('Y-m-d');
                  if(filter_var($data->heading,FILTER_SANITIZE_STRING) && filter_var($data->assignedto,FILTER_VALIDATE_INT) && filter_var($data->priority,FILTER_SANITIZE_STRING) && filter_var($data->status,FILTER_SANITIZE_STRING) && filter_var($data->sprint_id,FILTER_VALIDATE_INT) && filter_var($data->version,FILTER_VALIDATE_FLOAT) && filter_var($data->points,FILTER_VALIDATE_FLOAT) && filter_var($data->description,FILTER_SANITIZE_STRING) && in_array($data->status,["OPEN","CLOSED","IN PROGRESS","QA"],true) && in_array($data->priority,["LOW","HIGH","MED"],true))
                  {   
                      $result = $user->getDetails($data->assignedto);
                      $result=$result->fetch(PDO::FETCH_ASSOC);
                      $team_id = $result["team_id"];
                      
                      if($issue->createIssue($data,$user_id,$team_id,$date)) 
                      {       
                              http_response_code(201);
                              $result=$issue->currentIssue();
                              $result=$result->fetch(PDO::FETCH_ASSOC);

                              $arr["issue_id"] = $result["issue_id"];

                              $issue_item = array(
                                'issue_id' => $arr["issue_id"],
                                'heading' => $result["heading"],
                                'reportedby' => $result["reportedby"],
                                'assignedto' => $result["assignedto"],
                                'team_id' => $result["team_id"],
                                'priority' => $result["priority"],
                                'status' => $result["status"],
                                'sprint_id' => $result["sprint_id"],
                                'version' => $result["version"],
                                'points' => $result["points"],
                                'date' => $result["date"],
                                'description' => $result["description"],
                                'comments'=> $issue->readComments($arr)
                              );

                              echo json_encode(
                                array('message' => 'Issue created!',
                                      'issue' => $issue_item)
                              );
                      }
                  }
                  else
                  {
                      http_response_code(400);
                      echo json_encode(
                        array('error' => 'CREATE_FAILED',
                              'message'=>'Check Params')
                      );
                  }
              }
              elseif(isset($data->comment) && isset($data->issue_id))
              {
                  if(filter_var($data->comment,FILTER_SANITIZE_STRING) && filter_var($data->issue_id,FILTER_VALIDATE_INT))
                  {
                    $temp["issue_id"]=$data->issue_id;
                    $test=$issue->readSingle($temp);
                    if($test->rowCount()!=0)
                    {
                      if($issue->addComment($data,$user_id)) 
                      {       
                              http_response_code(201);

                              $comment=$issue->readComments($temp);

                              echo json_encode(
                                array('message' => 'Comment added!',
                                      'comments' => $comment)
                              );
                      }
                    }
                    else
                    {
                          http_response_code(400);
                          echo json_encode(
                            array('error' => 'CREATE_FAILED',
                                  'message'=>'Issue not found')
                          );
                    }
                  }
                  else
                  {
                      http_response_code(400);
                      echo json_encode(
                        array('error' => 'CREATE_FAILED',
                              'message'=>'Check Params')
                      );
                  }
              }
              else
              {
                  http_response_code(406);
                  echo json_encode(
                    array('error' => 'CREATE_FAILED',
                          'message'=>'Missing Parameters')
                  );
              }
          }
          elseif($request=="PUT")
          {
                  $data = json_decode(file_get_contents("php://input"));
                  if(isset($data->issue_id) && isset($data->heading) && isset($data->assignedto) && isset($data->priority) && isset($data->status) && isset($data->sprint_id) && isset($data->version) && isset($data->points) && isset($data->description))
                  {
          
                      if(filter_var($data->issue_id,FILTER_VALIDATE_INT) && filter_var($data->heading,FILTER_SANITIZE_STRING) && filter_var($data->assignedto,FILTER_VALIDATE_INT) && filter_var($data->priority,FILTER_SANITIZE_STRING) && filter_var($data->status,FILTER_SANITIZE_STRING) && filter_var($data->sprint_id,FILTER_VALIDATE_INT) && filter_var($data->version,FILTER_VALIDATE_FLOAT) && filter_var($data->points,FILTER_VALIDATE_FLOAT) && filter_var($data->description,FILTER_SANITIZE_STRING) && in_array($data->status,["OPEN","CLOSED","IN PROGRESS","QA"],true) && in_array($data->priority,["LOW","HIGH","MED"],true))
                      {
                          $d["issue_id"]=$data->issue_id;
                          $test=$issue->readSingle($d);

                          if($test->rowCount()!=0)
                          {
                            $result = $user->getDetails($data->assignedto);
                            $result=$result->fetch(PDO::FETCH_ASSOC);
                            $team_id = $result["team_id"];

                            if($issue->updateIssue($data,$user_id,$team_id)) 
                            {
                                  $result=$issue->readSingle($d);

                                  $result=$result->fetch(PDO::FETCH_ASSOC);

                                  $arr["issue_id"] = $result["issue_id"];

                                  $issue_item = array(
                                    'issue_id' => $arr["issue_id"],
                                    'heading' => $result["heading"],
                                    'reportedby' => $result["reportedby"],
                                    'assignedto' => $result["assignedto"],
                                    'team_id' => $result["team_id"],
                                    'priority' => $result["priority"],
                                    'status' => $result["status"],
                                    'sprint_id' => $result["sprint_id"],
                                    'version' => $result["version"],
                                    'points' => $result["points"],
                                    'date' => $result["date"],
                                    'description' => $result["description"],
                                    'comments'=> $issue->readComments($arr)
                                  );
                                    echo json_encode(
                                      array('message' => 'Issue updated!',
                                            'issue'=> $issue_item)
                                    );
                            }
                          }
                          else
                          {
                              http_response_code(400);
                              echo json_encode(
                                array('error' => 'UPDATE_FAILED',
                                      'message'=>'Issue not found')
                              );
                          }
                      }
                      else
                      {
                          http_response_code(400);
                          echo json_encode(
                            array('error' => 'UPDATE_FAILED',
                                  'message'=>'Check Params')
                          );
                      }
                  }
                  else
                  {
                      http_response_code(406);
                      echo json_encode(
                        array('error' => 'UPDATE_FAILED',
                              'message'=>'Missing Params')
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
        $issue->close();
  }

  ?>