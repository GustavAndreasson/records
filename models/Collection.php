<?php

class Collection {
    private $discogsUsername;
    private $userId;
    private $releases;
    private $conn;

    public function __construct($conn, $user) {
        $this->conn = $conn;

        try {
            $stmt = $this->conn->prepare("select id from users where username = ?");
            $stmt->execute(array($user));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $this->userId = $result["id"];
            } else {
                $stmt = $this->conn->prepare("insert into users (username) values (?)");
                $stmt->execute(array($user));
                $this->userId = $this->conn->lastInsertId();
            }
        } catch(PDOException $e) {
            Util::log("Something went wrong when looking up or creating user: " . $e->getMessage(), true);
        }

        setcookie("discogs_username", $user, time() + (86400 * 30), "/");
        $this->discogsUsername = $user;
        $this->releases = array();
    }

    public function updateCollection($page, $pageSize) {
        //$this->loadCollection();
        Util::log("Updating  collection for " . $this->discogsUsername . " page " . $page);

        $data = Discogs::getCollection($this->discogsUsername, $page, $pageSize);
        $releasesData = $data->releases;

        $getReleasesUrl = "";
        if (isset($data->pagination->urls->next)) {
            $last = false;
        } else {
            $last = true;
        }
        $oldReleases = array();
        try {
            $stmt = $this->conn->prepare("select record_id from user_records where user_id = ?");
            $stmt->execute(array($this->userId));
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $oldReleases[] = $row["record_id"];
            }
        } catch (PDOException $e) {
            Util::log("Something went wrong reading user collection: " . $e->getMessage(), true);
        }

        Util::log("Storing collection updates for " . $this->discogsUsername);
        $this->conn->beginTransaction();
        $newReleases = array();
        foreach($releasesData as $releaseData) {
            if (!in_array($releaseData->id, $oldReleases)) {
                $release = new Record($this->conn, $releaseData->basic_information);
                $release->addedDate = $releaseData->date_added;
                try {
                    $sql = "insert into user_records (user_id, record_id, added_date) values (?, ?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute(array($this->userId, $release->id, substr($releaseData->date_added, 0, 10)));
                } catch (PDOException $e) {
                    Util::log("Something went wrong when adding record to user: " . $e->getMessage(), true);
                }
                $newReleases[] = $release;
                $this->releases[$release->id] = $release;
            }
        }
        $this->conn->commit();

        Util::log("Done storing collection updates for " . $this->discogsUsername);

        return array('releases' => $newReleases, 'last' => $last);
    }

    public function getCollection() {
        $this->loadCollection();
        return $this->releases;
    }

    private function loadCollection() {
        if ($this->releases) {
            return;
        }
        Util::log("Loading collection for " . $this->discogsUsername);
        try {
            $stmt = $this->conn->prepare("select * from user_records where user_id = ?");
            $stmt->execute(array($this->userId));
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $release = new Record($this->conn, $row["record_id"]);
                $release->addedDate = $row["added_date"];
                $this->releases[$release->id] = $release;
            }
        } catch (PDOException $e) {
            Util::log("Something went wrong when collecting records for user: " . $e->getMessage(), true);
        }
        return;
    }
}
