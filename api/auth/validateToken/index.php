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

      if($request=="POST")
      {
          include_once '../../../config/Database.php';
          include_once '../../../models/User.php';

          // Instantiate DB & connect
          $database = new Database();
          $db = $database->connect();

          //New user object
          $user = new User($db);
          
          $header=getallheaders();
          $auth = $header['Authorization'];
          $auth = str_replace("Bearer ","",$auth);
          
          $e = $user->validateToken($auth);

          if($e>-1)
          {
              echo json_encode(
                array('message'=> 'Valid Token')
              );
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
      }
  }

?>