<?php 
  class Issue {
    // DB stuff
    private $conn;

    //User Properties
    private $issue_id;
    private $heading;
    private $reportedby;
    private $assignedto;
    private $priority;
    private $status;
    private $sprint_id;
    private $version;
    private $points;
    private $date;
    private $description;

    // Constructor with DB
    public function __construct($db) {
      $this->conn = $db;
    }

    public function currentIssue() {

      $query = 'SELECT * FROM issues WHERE issue_id=(SELECT max(issue_id) from issues)';
      
      $stmt = $this->conn->prepare($query);

      $stmt->execute();

      

      return $stmt;
    }

    public function readAll($arr) {
        try
        {
            if(isset($arr["user_id"]))
            {
              $query = 'SELECT * FROM issues WHERE sprint_id = ? AND assignedto = ?';
              $stmt = $this->conn->prepare($query);
              $stmt->execute([$arr["sprint_id"],$arr["user_id"]]);
            }
            elseif(isset($arr["team_id"]))
            {
              $query = 'SELECT * FROM issues WHERE sprint_id=? AND team_id = ?';
              $stmt = $this->conn->prepare($query);
              $stmt->execute([$arr["sprint_id"],$arr["team_id"]]);
            }
            else
            {
                  $query = 'SELECT * FROM issues WHERE sprint_id = ?';
                  $stmt = $this->conn->prepare($query);
                  $stmt->execute([$arr["sprint_id"]]);
            }
            
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
   
    public function readSingle($arr) {
        try
        {
              $query = 'SELECT * FROM issues WHERE issue_id = ?';
              $stmt = $this->conn->prepare($query); 
              $stmt->execute([$arr["issue_id"]]);
              
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
  
    public function createIssue($arr,$user_id,$team_id,$date) {
        try
        {
              $query = 'INSERT INTO issues values(null,?,?,?,?,?,?,?,?,?,?,?)';
            
              $stmt = $this->conn->prepare($query);

              if($stmt->execute([$arr->heading,$user_id,$arr->assignedto,$team_id,$arr->priority,$arr->status,$arr->sprint_id,$arr->version,$arr->points,$date,$arr->description]))  
                return True;
              else
                return False;
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
    
    public function updateIssue($arr,$user_id,$team_id) {
        try
        {
          $this->issue_id = $arr->issue_id;

          $query = 'UPDATE issues SET heading = ?, assignedto = ?, team_id = ?, priority = ?, status = ?, sprint_id = ?, version = ?, points = ?, description = ? WHERE issue_id ='.$this->issue_id;
          
          $stmt = $this->conn->prepare($query);

          
    
          $stmt->execute([$arr->heading,$arr->assignedto,$team_id,$arr->priority,$arr->status,$arr->sprint_id,$arr->version,$arr->points,$arr->description]);

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

    public function readComments($arr){
        try
        {
          $query = 'SELECT * FROM comments WHERE issue_id = ?';
          
          $stmt = $this->conn->prepare($query);
  
          $stmt->execute([$arr["issue_id"]]);
  
          $num = $stmt->rowCount();
  
          $comments_arr = array();
  
            if($num!=0) 
            {    
              
                while($row = $stmt->fetch(PDO::FETCH_ASSOC))
                {
                  extract($row);
                  $comments_item = array(
                    'comment_id' => html_entity_decode($comment_id),
                    'issue_id' => html_entity_decode($issue_id),
                    'comment' => html_entity_decode($comment),
                    'user_id' => html_entity_decode($user_id),
                  );
            
                
                  array_push($comments_arr, $comments_item);
                }
            }
            else
            { 
                  $comments_arr = array();
            }

          

          return $comments_arr;

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

      public function addComment($arr,$user_id) {

        $query = 'INSERT INTO comments values(null,?,?,?)';
        
        $stmt = $this->conn->prepare($query);
        
        
        
        if($stmt->execute([$arr->issue_id,$arr->comment,$user_id]))  
          return True;
        else
          return False;
      }

      public function close()
      {
        $this->conn=null;
      }
  }

?>