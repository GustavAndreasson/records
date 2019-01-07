<?php

class Track {
    private $id;
    private $conn;
    private $record_id;
    public $position;
    public $name;
    public $artists;

    public function __construct($conn, $track, $record_id) {
        $this->conn = $conn;
        $this->record_id = $record_id;
        if (is_object($track)) { // store new track in database with data from discogs data
            $this->position = $track->position;
            $this->name = $track->title;
            try {
                $stmt = $this->conn->prepare("insert into tracks (record_id, position, name) values (?, ?, ?)");
                $stmt->execute(array($this->record_id, $this->position, $this->name));
                $this->id = $this->conn->lastInsertId();
                if (isset($track->artists)) {
                    $pos = 1;
                    foreach ($track->artists as $artist) {
                        $this->artists[$pos] = [
                            "artist" => new Artist($this->conn, $artist),
                            "delimiter" => $artist->join
                        ];
                        $sql = "insert into track_artists (track_id, artist_id, delimiter, position) values (?, ?, ?, ?)";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->execute(array($this->id, $artist->id, $artist->join, $pos));
                        $pos += 1;
                    }
                }
            } catch (PDOException $e) {
                Util::log("Something went wrong when adding track data: " . $e->getMessage(), true);
            }
        } else { // create track from database data
            $this->id = $track["id"];
            $this->position = $track["position"];
            $this->name = $track["name"];
            try {
                $sql = "select a.id, a.name, ta.delimiter, ta.position from track_artists ta ";
                $sql .= "inner join artists a on ta.artist_id = a.id ";
                $sql .= "where ta.track_id = ? order by ta.position asc";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute(array($this->id));
                $pos = 0;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pos = $row["position"] ?: $pos + 1;
                    $this->artists[$pos] = [
                        "artist" => new Artist($this->conn, ["id" => $row["id"], "name" => $row["name"]]),
                        "delimiter" => $row["delimiter"]
                    ];
                }
            } catch (PDOException $e) {
                Util::log("Something went wrong when getting track data: " . $e->getMessage(), true);
            }
        }
    }
}
