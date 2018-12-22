<?php

class Artist {
    private $id;
    private $conn;
    public $name;
    public $description;
    public $members;
    public $groups;

    public function __construct($conn, $artist) {
        $this->conn = $conn;
        $this->members = array();
        $this->groups = array();
        if (is_object($artist)) { // store new artist in database with data from discogs data
            $this->id = $artist->id;
            $this->name = $artist->name;
            $this->description = $artist->profile;
            if (isset($artist->members)) {
                foreach ($artist->members as $member) {
                    $this->members[] = [
                        "artist" => new Artist(
                            $this->conn, ["id" => $member->id,
                            "name" => preg_replace("/\s\([0-9]+\)/", "", $member->name)]),
                        "active" => $member->active
                    ];
                }
            }
            if (isset($artist->groups)) {
                foreach ($artist->groups as $group) {
                    $this->groups[] = [
                        "artist" => new Artist(
                            $this->conn, ["id" => $group->id,
                            "name" => preg_replace("/\s\([0-9]+\)/", "", $group->name)]),
                        "active" => $group->active
                    ];
                }
            }
        } elseif (is_array($artist)) { // data already collected from the database 
            $this->id = $artist["id"];
            $this->name = $artist["name"];
            if (isset($artist["description"])) {
                $this->description = $artist["description"];
            }
            /*if (isset($artist["members"])) {
                $this->members = $artist["members"];
                }*/
        } else { // collect data from database
            $this->id = $artist;
            $sql = "select * from artist where id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array($this->id));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $this->name = $result["name"];
                //$this->description = $result["description"];
            }
        }
    }
}
