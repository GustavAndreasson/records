<?php

class Main {
    public $collection;
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host=" . DB_SERVERNAME . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
            // set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            Util::log("Connection failed: " . $e->getMessage(), true);
            die();
        }

        Discogs::setUserAgent("gustav_records/0.1");
        Discogs::setKey(DISCOGS_KEY);
        Discogs::setSecret(DISCOGS_SECRET);
        Discogs::setDatabaseConnection($this->conn);

        if (isset($_REQUEST["username"])) {
            $this->collection = new Collection($this->conn, $_REQUEST["username"]);
        } elseif (isset($_COOKIE["discogs_username"])) {
            $this->collection = new Collection($this->conn, $_COOKIE["discogs_username"]);
        }
    }

    public function setUsername($name) {
        $this->collection = new Collection($this->conn, $name);
    }

    public function updateRecords() {
        echo date("Y-m-d H:i:s") . " Begin updating records...\n";
        $counter = 0;
        try {
            $stmt = $this->conn->prepare("select id from records where updated is null limit 1000");
            $stmt->execute(array());
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo date("Y-m-d H:i:s") . " Updating record with id " . $row["id"] . "...\n";
                $releaseData = Discogs::getRelease($row["id"]);
                if (isset($releaseData->master_id)) {
                    $masterData = Discogs::getMaster($releaseData->master_id);
                } else {
                    $masterData = null;
                }
                $record = new Record($this->conn, $row["id"]);
                $record->addData($releaseData, $masterData);
                echo date("Y-m-d H:i:s") . " Updated " . $record->name . "\n";
                $counter += 1;
            }
        } catch (PDOException $e) {
            Util::log("Something went wrong when collecting records for update: " . $e->getMessage(), true);
        }
        Discogs::clearAccessLog();
        echo date("Y-m-d H:i:s") . " Done updating $counter records.\n";
    }
}
