<?php 
  // Headers
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');
  header('Access-Control-Allow-Methods: POST, OPTIONS');
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
      include_once '../../../models/User.php';
    
      // Instantiate DB & connect
      $database = new Database();
      $db = $database->connect();
    
      //New user object
      $user = new User($db);  
      
      if($request=="POST")
      {
          if(isset($data->username) && isset($data->password))
          {
              if(filter_var($data->username,FILTER_VALIDATE_EMAIL))
              {
                  $pass = $user->encrypt($data->password);
                  $result = $user->readSingle($data->username);
                  $final = $result->fetch(PDO::FETCH_ASSOC);
          
                  $num = $result->rowCount();
          
                  if($num!=0) 
                  {
                        $username = $final['username'];
                        if($pass==$final['password'])
                        {   
                          $token = $user->validateToken($final['user_id']);
                          if($token=="-1" || $token=="-2")
                          { 
                            $token = $user->createToken($final["user_id"]);
                          }
          
                          echo json_encode(
                            array('message' => 'Success',
                                  'token' => $token)
                          );
                        } 
                        else 
                        {   http_response_code(401);
                            echo json_encode(
                            array('error' => 'LOGIN_FAILED',
                                  'message'=> 'Incorrect Password')
                          );
                        }
                  }
                  else
                  {       
                          http_response_code(404);
                          echo json_encode(
                            array('error' => 'LOGIN_FAILED',
                                  'message'=>'User not found')
                          );
                  }
              }
              else
              {
                  http_response_code(400);
                  echo json_encode(
                    array('error' => 'LOGIN_FAILED',
                          'message' => 'Invalid email')
                  );
              }
          }
          else
          {       http_response_code(406);    
                  echo json_encode(
                    array('error'=> 'LOGIN_FAILED',
                          'message'=>'Missing parameters')
                  );
          }

        $user->close();
      }
  }
  

?>