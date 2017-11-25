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
    public $added_date;
    public $tracks;
    
    public function __construct($conn, $record) {
        $this->conn = $conn;
        if (is_object($record)) { //record is an record object from discogs
            //TODO: Check with isset before setting attributes
            $this->id = $record->id;
            $this->name = $record->basic_information->title;
            foreach ($record->basic_information->artists as $artist) {
                $this->artists[] = ["name" => preg_replace("/\s\([0-9]+\)/", "", $artist->name),
                                    "delimiter" => $artist->join];
            }
            $this->cover = $record->basic_information->cover_image;
            $this->thumbnail = $record->basic_information->thumb;
            $this->format = $record->basic_information->formats[0]->name;
            $format_descriptions = array();
            if (isset($record->basic_information->formats[0]->descriptions)) {
                $format_descriptions = $record->basic_information->formats[0]->descriptions;
            }
            if (in_array("12\"", $format_descriptions)) {
                $this->format = "Vinyl12";
            } else if (in_array("10\"", $format_descriptions)) {
                $this->format = "Vinyl10";
            } else if (in_array("7\"", $format_descriptions)) {
                $this->format = "Vinyl7";
            } else {
                $this->format = $record->basic_information->formats[0]->name;
            }
            $this->year = $record->basic_information->year;
            $this->added_date = $record->date_added;
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
                    foreach ($record->basic_information->artists as $artist) {
                        $stmt = $conn->prepare("insert into artists (id, name) values (?, ?) on duplicate key update id=id");
                        $stmt->execute(array($artist->id, preg_replace("/\s\([0-9]+\)/", "", $artist->name)));
                        $stmt = $conn->prepare("insert into record_artists (record_id, artist_id, delimiter) values (?, ?, ?)");
                        $stmt->execute(array($this->id, $artist->id, $artist->join));
                    }
                }
            } catch (PDOException $e) {
                Util::log("Something went wrong when creating record: " . $e->getMessage(), true);
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
                $sql = "select a.name, ra.delimiter from record_artists ra ";
                $sql .= "inner join artists a on ra.artist_id = a.id ";
                $sql .= "where record_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute(array($this->id));
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->artists[] = ["name" => $row["name"],
                                        "delimiter" => $row["delimiter"]];
                }
                $stmt = $this->conn->prepare("select id, position, name from tracks where record_id = ?");
                $stmt->execute(array($this->id));
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->tracks[] = new Track($this->conn, $row, $this->id);
                }
            } catch (PDOException $e) {
                Util::log("Something went wrong when getting record: " . $e->getMessage(), true);
            }
        }
    }

    public function addData($releaseData, $masterData) {
        try {
            $this->conn->beginTransaction();
            if (!$this->tracks && isset($releaseData->tracklist)) {
                foreach ($releaseData->tracklist as $track) {
                    $this->tracks[] = new Track($this->conn, $track, $this->id);
                }
            }
            if (!$this->thumbnail && isset($masterData->images[0])) {
                $this->thumbnail = $masterData->images[0]->uri150;
                $this->cover = $masterData->images[0]->uri;
            }
            if (isset($masterData->year)) {
                $this->year = $masterData->year;
            }
            $now = date("Y-m-d");
            $stmt = $this->conn->prepare("update records set year = ?, updated = ?, cover = ?, thumbnail = ? where id = ?");
            $stmt->execute(array($this->year, $now, $this->cover, $this->thumbnail, $this->id));
            $this->conn->commit();
        } catch (PDOException $e) {
            Util::log("Something went wrong when adding record data: " . $e->getMessage(), true);
        }
    }
}
