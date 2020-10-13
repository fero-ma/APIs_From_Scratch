<?php 
  class User {
    // DB stuff
    private $conn;
    private $key='GoalWise';

    //User Properties
    private $user_id;
    private $username;
    private $password;
    private $token;
    private $expiry;

    public $result;

    // Constructor with DB
    public function __construct($db) {
      $this->conn = $db;
    }
    
    public function getDetails($user_id) {
      try
      {
          $this->user_id = $user_id;

          $query = 'SELECT * FROM user_details WHERE user_id = ?';
          
          $stmt = $this->conn->prepare($query);
    
          $stmt->execute([$this->user_id]);

          return $stmt;
      }
      catch(Exception $exception)
      {
          http_response_code(504);
          echo json_encode(
          array('error' => $exception->getMessage())
          );
          die();
      } 
    }

    public function readSingle($username) {
      try
      {
          $this->username = $username;

          $query = 'SELECT * FROM users WHERE username = ? AND active = 1';
          
          $stmt = $this->conn->prepare($query);
    
          $stmt->execute([$this->username]);

          
    
          return $stmt;
      }
      catch(Exception $exception)
      {
          http_response_code(504);
          echo json_encode(
          array('error' => $exception->getMessage())
          );
          die();
      } 
    }
  
    public function encrypt($data) {
      
      return hash('sha256',hash('sha256',md5($this->key).$data));

    }

    public function createToken($user_id) {
      try
      {
        $issuetime=time();
        $expiry=time()+54000;
        $token = hash('sha256',$issuetime.$expiry.$this->encrypt($this->username));
  
        $query = 'INSERT INTO sessions values(?,?,?,?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id,$this->username,$token,$expiry]);

        
  
        return $token;
      }
      catch(Exception $exception)
      {
          http_response_code(504);
          echo json_encode(
          array('error' => $exception->getMessage())
          );
          die();
      } 
    

    }

    public function validateToken($token) {
      try
      {
          $result = $this->getTokenDetails($token);

          $num = $result->rowCount();

          if($num!=0) 
          {  
              $row = $result->fetch(PDO::FETCH_ASSOC);
              extract($row);
              $this->user_id = html_entity_decode($user_id);
              $this->expiry = html_entity_decode($expiry);
              if(time()<$this->expiry)
                  return $token;
              else
              {
                  $query = 'DELETE FROM sessions WHERE token = ? OR user_id = ?';
                  $stmt = $this->conn->prepare($query);
                  $stmt->execute([$token,$token]);

                    

                  return -1;
              }
          }
          else
              return -2;

      }
      catch(Exception $exception)
      {
          http_response_code(504);
          echo json_encode(
          array('error' => $exception->getMessage())
          );
          die();
      } 

    }

    public function getTokenDetails($token)
    {
      try
      {
          $query = 'SELECT * FROM sessions WHERE token = ? OR user_id = ?';
          $stmt = $this->conn->prepare($query);
          $stmt->execute([$token,$token]);

          
          
          return $stmt;
      }
      catch(Exception $exception)
      {
          http_response_code(504);
          echo json_encode(
          array('error' => $exception->getMessage())
          );
          die();
      } 
    }

    public function close()
    {
      $this->conn=null;
    }

  }