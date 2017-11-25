<?php

class Track {
    private $id;
    private $conn;
    private $record_id;
    public $position;
    public $name;
    public $artists;

    public function __construct($conn, $data, $record_id) {
        $this->conn = $conn;
        $this->record_id = $record_id;
        if (is_object($data)) { // store new track in database with data from discogs data
            $this->position = $data->position;
            $this->name = $data->title;
            try {
                $stmt = $this->conn->prepare("insert into tracks (record_id, position, name) values (?, ?, ?)");
                $stmt->execute(array($this->record_id, $this->position, $this->name));
                $this->id = $this->conn->lastInsertId();
                if (isset($data->artists)) {
                    foreach ($data->artists as $artist) {
                        $this->artists[] = ["name" => $artist->name,
                                            "delimiter" => $artist->join];
                        $stmt = $this->conn->prepare("insert into artists (id, name) values (?, ?) on duplicate key update id=id");
                        $stmt->execute(array($artist->id, preg_replace("/\s\([0-9]+\)/", "", $artist->name)));
                        $stmt = $this->conn->prepare("insert into track_artists (track_id, artist_id, delimiter) values (?, ?, ?)");
                        $stmt->execute(array($this->id, $artist->id, $artist->join));
                    }
                }
            } catch (PDOException $e) {
                Util::log("Something went wrong when adding track data: " . $e->getMessage(), true);
            }
        } else { // create track from database data
            $this->id = $data["id"];
            $this->position = $data["position"];
            $this->name = $data["name"];
            $sql = "select a.name, ta.delimiter from track_artists ta ";
            $sql .= "inner join artists a on ta.artist_id = a.id ";
            $sql .= "where ta.track_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array($this->id));
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->artists[] = ["name" => $row["name"],
                                    "delimiter" => $row["delimiter"]];
            }
        }
    }
}
