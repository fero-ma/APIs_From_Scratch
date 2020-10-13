<?php
    class User_Details{
        private $conn;

        public $username;
        public $password;
        public $user_id;
        public $fullname;
        public $nationality;
        public $team_id;
        public $image;
        public $role;
        public $language;
        public $pass;
        public $retype_pass;
        public $response = array();

        public function __construct($db){
            $this->conn = $db;
        }

        public function create_user_and_details(){
            try{
                $query1= "INSERT INTO users SET username = :username, password = :password";
                $stmt = $this->conn->prepare($query1);

                $stmt->bindParam(":username", $this->username);
                $stmt->bindParam(":password", $this->password);
                if($stmt->execute()){
                    $user_id = $this->conn->lastInsertId();
                    $query2 = 'INSERT INTO user_details SET user_id = :user_id, fullname = :fullname, 
                    nationality = :nationality, team_id = :team_id, role = :role';
                    $stmt= $this->conn->prepare($query2);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':fullname', $this->fullname);
                    $stmt->bindParam(':nationality', $this->nationality);
                    $stmt->bindParam(':team_id', $this->team_id);
                    $stmt->bindParam(':role', $this->role);
                    
                    $stmt->execute();
                    return $user_id;
                }else{
                    $result["Error"] = "INSERT_FAILED";
                    $result["Message"] = "CHECK_PARAMS";
                    http_response_code(400);
                    return $result;
                }
                
            }
            catch(Exception $e){
                echo json_encode($e->getMessage());
            }
            
        }

        public function update_user_details(){
            try{
                $query = 'UPDATE user_details SET user_id = :user_id, fullname = :fullname,
                nationality = :nationality, team_id = :team_id, role = :role WHERE user_id = :user_id';
                $stmt= $this->conn->prepare($query);
                
                $stmt->bindParam(':user_id', $this->user_id);
                $stmt->bindParam(':fullname', $this->fullname);
                $stmt->bindParam(':nationality', $this->nationality);
                $stmt->bindParam(':team_id', $this->team_id);
                $stmt->bindParam(':role', $this->role);
                
                
                if($stmt->execute()){
                    // CONTINUE
                }
                else{
                    $result['message'] = 'SERVER_ERROR';
                    http_response_code(504);
                    print_r(json_encode($result));
                    die();
                }  
            }
            catch(Exception $e){
                echo json_encode($e->getMessage());
            }
        }

        public function delete_user(){
            try{
                $query ="UPDATE users SET active = 0 WHERE user_id =:user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":user_id", $this->user_id);
                
                if($stmt->execute()){
                    $result['Message'] = 'USER_DELETED';
                    http_response_code(200);   
                }
                else{
                    $result['message'] = 'SERVER_ERROR';
                    http_response_code(504);
                }
                return $result; 
            }
            catch(Exception $e){
                echo json_encode($e->getMessage());
            }
            
        }

        public function readSingle(){
            try{
                $query = "SELECT * FROM user_details WHERE user_id = :user_id";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":user_id", $this->user_id);

                $stmt->execute();

                return $stmt;
            }
            catch(Exception $e)
            {
                echo json_encode($e->getMessage());
            }
        }

        public function getUserIdName(){
            $query = "SELECT user_id,fullname,team_id FROM user_details";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        }

        public function member_details(){
            try{
                $query = "SELECT user_id,fullname,nationality,team_id,image,role FROM user_details WHERE team_id = ? and user_id <> $this->user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $this->team_id);
                $stmt->execute();

                return $stmt;
            }
            catch(Exception $e){
                echo json_encode($e->getMessage());
            }
        }

        public function change_photo(){
            try{
        
                if(is_uploaded_file($_FILES['file']['tmp_name'])){
                    $tmpname = $_FILES['file']['tmp_name'];
                    $img_name = $_FILES['file']['name'];
                    $img_extn = '.'.pathinfo($img_name, PATHINFO_EXTENSION);
                    // SET THE DIRECTORY WHERE YOU WANT TO MOVE THE FILE WITH THE NAME IN WHICH\
                    // YOU WANT TO SAVE
                    $hashed_img = md5($img_name).$img_extn;
                    $dir = 'images/'. $hashed_img;
                    //$tmpname = file_get_contents($tmpname);
            
                    if(move_uploaded_file($tmpname,$dir)){
                        //execute this query in here and if this is a success. then return a path in response 
                        //http://localhost/projectmanagementtool/projectmanagementtool/api/user_details/ . $dir.
                        $path = "http://gl.kamarcutu.tk/api/user_details/$dir";
                        $query = "UPDATE user_details SET image = '$path' WHERE user_id = ".$this->user_id;        
                        $stmt = $this->conn->prepare($query);
                        if($stmt->execute()){
                            $this->response["Success"] ="UPLOAD_SUCCESS";
                            $this->response["Message"] ="PROFILE_PICTURE_UPDATED";
                            //$this->response['location'] = $dir;
                            $this->response["Path"] = $path;
                            http_response_code(200);
                        }
                    }
                    else{
                        $this->response["Error"] = "SERVER_ERROR";
                        http_response_code(504);
                        // $this->response['location'] = $img_extn;
                    }
                
                }
                else{
                    $this->response["Error"] = "UPLOAD_FAILED";
                    $this->response["Message"] ="MISSING_PARAMS";
                    http_response_code(404);
                }
                return $this->response;
            }
            catch(Exception $e){
                echo json_encode($e->getMessage());
            }
            
        }

        public function change_password(){
            try{
                if($this->pass == $this->retype_pass){
                    $query = "UPDATE users SET password = '$this->pass' WHERE user_id = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1, $this->user_id);
                    
                    if($stmt->execute()){
                        $this->response["Success"] ="UPDATE_SUCCESS";
                        $this->response["Message"] = "PASSWORD_CHANGED";
                        http_response_code(200);
                    }
                    else{
                        $this->response["Error"] ="UPDATE_FAILED";
                        $this->response["Message"] ="SERVER_ERROR";
                        http_response_code(504);
                        
                    }
                }
                else{
                    $this->response["Error"] = "UPDATE_FAILED";
                    $this->response["Message"] = "CHECK_PARAMS";
                    http_response_code(404);
                    
                }
                return $this->response;
            }
            catch(Exception $e){
                echo json_encode($e->getMessage());
            }
        }

        public function close()
        {
        $this->conn=null;
        }

    }

?>