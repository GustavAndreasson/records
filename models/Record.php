<?php

class Record {
    private $conn;
    public $id;
    public $name;
    public $artists;
    public $cover;
    public $thumbnail;
    public $format;
    public $year;
    public $addedDate;
    public $tracks;

    public function __construct($conn, $record) {
        $this->conn = $conn;
        if (is_object($record)) { //record is an record object from discogs
            //TODO: Check with isset before setting attributes
            $this->id = $record->id;
            $this->name = $record->title;


            $this->cover = $record->cover_image ?? null;
            $this->thumbnail = $record->thumb ?? null;
            if (isset($record->formats)) {
                $this->setFormat($record->formats);
            }
            $this->year = $record->year ?? null;
            try {
                $stmt = $this->conn->prepare("select id from records where id = ?");
                $stmt->execute(array($this->id));
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$result) { //check that the record does not already exist
                    $sql = "insert into records (id, name, cover, thumbnail, format, year) values (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute(array(
                        $this->id,
                        $this->name,
                        $this->cover,
                        $this->thumbnail,
                        $this->format,
                        $this->year
                    ));
                    if (isset($record->artists)) {
                        $this->setArtists($record->artists);
                    }

                }
            } catch (PDOException $e) {
                Util::log("Something went wrong when creating record: " . $e->getMessage(), true);
                throw($e);
            }
        } else { //record is an id of a record in the database
            $this->id = $record;
            try {
                $stmt = $this->conn->prepare("select * from records where id = ?");
                $stmt->execute(array($this->id));
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $this->name = $result["name"];
                    $this->cover = $result["cover"];
                    $this->thumbnail = $result["thumbnail"];
                    $this->format = $result["format"];
                    $this->year = $result["year"];
                }
                $sql = "select a.id, a.name, ra.delimiter, ra.position from record_artists ra ";
                $sql .= "inner join artists a on ra.artist_id = a.id ";
                $sql .= "where record_id = ? order by ra.position asc";
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
                $stmt = $this->conn->prepare("select id, position, name from tracks where record_id = ?");
                $stmt->execute(array($this->id));
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->tracks[] = new Track($this->conn, $row, $this->id);
                }
            } catch (PDOException $e) {
                Util::log("Something went wrong when getting record: " . $e->getMessage(), true);
                throw($e);
            }
        }
    }

    private function setArtists($artists) {
        $sql = "insert into record_artists (record_id, artist_id, delimiter, position) values ";
        $vals = array();
        $pos = 1;
        foreach ($artists as $artist) {
            $this->artists[$pos] = [
                "artist" => new Artist($this->conn, $artist),
                "delimiter" => $artist->join
            ];
            $sql .= "(?, ?, ?, ?),";
            $vals[] = $this->id;
            $vals[] = $artist->id;
            $vals[] = $artist->join;
            $vals[] = $pos++;
        }
        if ($vals) {
            $sql = rtrim($sql, ",");
            $sql .= "on duplicate key update ";
            $sql .= "delimiter=values(delimiter), position=values(position)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($vals);
        }
    }

    private function setFormat($formats) {
        $this->format = $formats[0]->name;
        $format_descriptions = array();
        if (isset($formats[0]->descriptions)) {
            $format_descriptions = $formats[0]->descriptions;
        }
        if (in_array("12\"", $format_descriptions)) {
            $this->format = "Vinyl12";
        } else if (in_array("10\"", $format_descriptions)) {
            $this->format = "Vinyl10";
        } else if (in_array("7\"", $format_descriptions)) {
            $this->format = "Vinyl7";
        }
    }

    public function addData($releaseData, $masterData) {
        try {
            $this->conn->beginTransaction();
            if (!$this->artists && isset($releaseData->artists)) {
                $this->setArtists($releaseData->artists);
            }
            if (!$this->tracks && isset($releaseData->tracklist)) {
                foreach ($releaseData->tracklist as $track) {
                    $this->tracks[] = new Track($this->conn, $track, $this->id);
                }
            }
            if (!$this->thumbnail && isset($masterData->images[0])) {
                $this->thumbnail = $masterData->images[0]->uri150;
            }
            if (!$this->cover && isset($masterData->images[0])) {
                $this->cover = $masterData->images[0]->uri;
            }
            if (!$this->format && isset($releaseData->formats)) {
                $this->setFormat($releaseData->formats);
            }
            if (isset($masterData->year)) {
                $this->year = $masterData->year;
            }
            $now = date("Y-m-d");
            $stmt = $this->conn->prepare("update records set year = ?, updated = ?, cover = ?, thumbnail = ?, format = ? where id = ?");
            $stmt->execute(array($this->year, $now, $this->cover, $this->thumbnail, $this->format, $this->id));
            $this->conn->commit();
        } catch (PDOException $e) {
            Util::log("Something went wrong when adding record data: " . $e->getMessage(), true);
            throw($e);
        }
    }
}
