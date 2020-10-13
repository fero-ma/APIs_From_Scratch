<?php

    class Team{

        private $conn;
        public $team_id;
        public $teamname;


        public function __construct($db)
        {
            $this->conn=$db;
        }

        public function teams()
        {
            $query = "SELECT * FROM teams";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $this->conn = null;
            return $stmt;
        }

        public function readSingleTeam()
        {
            $query = "SELECT * FROM teams WHERE team_id = :team_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam("team_id", $this->team_id);
            $stmt->execute();
            return $stmt;
        }

        public function create_team(){
            $query = "INSERT INTO teams SET teamname = :teamname";
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":teamname", $this->teamname);
            
            $stmt->execute();
            $id = $this->conn->lastInsertId();
            return $id;
        }

        public function close()
        {
        $this->conn=null;
        }

    }