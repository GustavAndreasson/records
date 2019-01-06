<?php

class Artist {
    private $conn;
    public $id;
    public $name;
    public $description;
    public $image;
    public $members;
    public $groups;
    public $updated;

    public function __construct($conn, $artist) {
        $this->conn = $conn;
        $this->members = array();
        $this->groups = array();
        if (is_object($artist)) { // store new artist in database with data from discogs data
            $this->id = $artist->id;
            $this->name = preg_replace("/\s\([0-9]+\)/", "", $artist->name);
            try {
                $stmt = $this->conn->prepare("select id, name from artists where id = ?");
                $stmt->execute(array($this->id));
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$result) { //check that the record does not already exist
                    $sql = "insert into artists (id, name) values (?, ?) ";
                    $sql .= "on duplicate key update name=values(name)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute(array($this->id, $this->name));
                }
            } catch (PDOException $e) {
                Util::log("Something went wrong when creating artist: " . $e->getMessage(), true);
            }
        } elseif (is_array($artist)) { // data already collected from the database
            $this->id = $artist["id"];
            $this->name = $artist["name"];
            if (isset($artist["description"])) {
                $this->description = $artist["description"];
            }
            if (isset($artist["image"])) {
                $this->description = $artist["image"];
            }
        } else { // collect data from database
            $this->id = $artist;
            try {
                $sql = "select * from artists where id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute(array($this->id));
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $this->name = $result["name"];
                    $this->description = $result["description"];
                    $this->image = $result["image"];
                    $this->updated = $result["updated"];
                }
                $sql = "select a.id, a.name, am.active from artist_members am ";
                $sql .= "inner join artists a on am.member_id = a.id ";
                $sql .= "where am.group_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute(array($this->id));
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->members[$row["id"]] = [
                        "artist" => new Artist($this->conn, ["id" => $row["id"], "name" => $row["name"]]),
                        "active" => $row["active"]
                    ];
                }
                $sql = "select a.id, a.name, am.active from artist_members am ";
                $sql .= "inner join artists a on am.group_id = a.id ";
                $sql .= "where am.member_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute(array($this->id));
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->groups[$row["id"]] = [
                        "artist" => new Artist($this->conn, ["id" => $row["id"], "name" => $row["name"]]),
                        "active" => $row["active"]
                    ];
                }
            } catch (PDOException $e) {
                Util::log("Something went wrong when getting artist: " . $e->getMessage(), true);
            }
        }
    }

    public function update() {
        $artist = Discogs::getArtist($this->id);
        $this->name = preg_replace("/\s\([0-9]+\)/", "", $artist->name);
        if (isset($artist->profile)) {
            $this->description = $artist->profile;
        }
        if (isset($artist->images[0])) {
            $this->image = $artist->images[0]->resource_url;
        }
        try {
            $stmt = $this->conn->prepare("select id, updated from artists where id = ?");
            $stmt->execute(array($this->id));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result || !$result['updated']) { //check that the record does not already exist or updated
                $now = date("Y-m-d");
                $sql = "insert into artists (id, name, description, image, updated) values (?, ?, ?, ?, ?) ";
                $sql .= "on duplicate key update ";
                $sql .= "name=values(name), description=values(description), image=values(image), updated=values(updated)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute(array($this->id, $this->name, $this->description, $this->image, $now));
                if (isset($artist->members)) {
                    $this->members = array();
                    foreach ($artist->members as $member) {
                        $this->members[$member->id] = [
                            "artist" => new Artist($this->conn, $member),
                            "active" => $member->active
                        ];
                        $sql = "insert into artist_members (group_id, member_id, active) values (?, ?, ?) ";
                        $sql .= "on duplicate key update active=values(active)";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->execute(array($this->id, $member->id, (int)$member->active));
                    }
                }
                if (isset($artist->groups)) {
                    $this->groups = array();
                    foreach ($artist->groups as $group) {
                        $this->groups[$group->id] = [
                            "artist" => new Artist($this->conn, $group),
                            "active" => $group->active
                        ];
                        $sql = "insert into artist_members (group_id, member_id, active) values (?, ?, ?) ";
                        $sql .= "on duplicate key update active=values(active)";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->execute(array($group->id, $this->id, (int)$group->active));
                    }
                }
            }
            $this->updated = true;
        } catch (PDOException $e) {
            Util::log("Something went wrong when updating artist: " . $e->getMessage(), true);
        }
    }
}
