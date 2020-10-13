<?php 
  class Sprint {
    // DB stuff
    private $conn;

    // Constructor with DB
    public function __construct($db) {
      $this->conn = $db;
    }

    public function currentSprint() {

      $query = 'SELECT * FROM sprints WHERE sprint_id=(SELECT max(sprint_id) from sprints)';
      
      $stmt = $this->conn->prepare($query);

      $stmt->execute();

      

      return $stmt;
    }

    public function readSingle($arr) {

      try
      {
          $query = 'SELECT * FROM sprints WHERE sprint_id = ?';
          
          $stmt = $this->conn->prepare($query);

          $stmt->execute([$arr["sprint_id"]]);

          

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

    public function readSprints() {

      $query = 'SELECT * FROM sprints ORDER BY sprint_id desc';
      
      $stmt = $this->conn->prepare($query); 

      $stmt->execute();

      

      return $stmt;
    }

    public function createSprint($startdate,$enddate,$duration,$user_id)
    {
        try
        {
          $query = 'INSERT INTO sprints values(null,?,?,?,?)';
            
          $stmt = $this->conn->prepare($query);

          if($stmt->execute([$startdate,$enddate,$duration,$user_id]))
          return True;
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

?>