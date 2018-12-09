<?php

class Discogs {
    const DISCOGS_BASE_URL = "https://api.discogs.com";
    
    private static $userAgent;
    private static $key;
    private static $secret;
    private static $conn;

    private static $lastCallTime = 0;
    private static $maxCalls = 60;
    private static $callsLeft = 60;

    public static function setUserAgent($userAgent) {
        self::$userAgent = $userAgent;
    }

    public static function setKey($key) {
        self::$key = $key;
    }

    public static function setSecret($secret) {
        self::$secret = $secret;
    }

    public static function setDatabaseConnection($conn) {
        self::$conn = $conn;
    }

    public static function clearAccessLog() {
        try {
            $stmt = self::$conn->prepare("delete from discogs_access where timestamp < ?");
            $stmt->execute(array(time() - 61));
        } catch (PDOException $e) {
            Util::log("Something went wrong when clearing discogs access log: " . $e->getMessage(), true);
        }
    }

    public static function getCollection($user, $page, $pageSize) {
        $uri = self::DISCOGS_BASE_URL . "/users/" . $user . "/collection/folders/0/releases";
        $uri .= "?page=" . $page . "&per_page=" . $pageSize;
        $uri .= "&key=" . self::$key . "&secret=" . self::$secret;
        return self::readUri($uri);
    }

    public static function getRelease($release) {
        $uri = self::DISCOGS_BASE_URL . "/releases/" . $release . "?curr_abr=SEK";
        $uri .= "&key=" . self::$key . "&secret=" . self::$secret;
        return self::readUri($uri);
    }

    public static function getMaster($master) {
        $uri = self::DISCOGS_BASE_URL . "/masters/" . $master . "?curr_abr=SEK";
        $uri .= "&key=" . self::$key . "&secret=" . self::$secret;
        return self::readUri($uri);
    }

    public static function getArtist($artist) {
        $uri = self::DISCOGS_BASE_URL . "/artists/" . $artist;
        $uri .= "&key=" . self::$key . "&secret=" . self::$secret;
        return self::readUri($uri);
    }

    public static function getArtistReleases($artist, $page, $pageSize) {
        $uri = self::DISCOGS_BASE_URL . "/artists/" . $artist . "/releases";
        $uri .= "?page=" . $page . "&per_page=" . $pageSize;
        $uri .= "&key=" . self::$key . "&secret=" . self::$secret;
        return self::readUri($uri);
    }

    public static function readUri($uri) {
        try {
            $stmt = self::$conn->prepare("select count(*) as queue, min(timestamp) as firstCall from discogs_access where timestamp > ?");
            $stmt->execute(array(time() - 60));
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($response) {
                if ($response["queue"] > self::$maxCalls - 2) {
                    Util::log("Too many discogs calls. Sleeping for " . (60 - (time() - $response["firstCall"])) . " seconds");
                    sleep(60 - (time() - $response["firstCall"]));
                }
            }
            
            $stmt = self::$conn->prepare("insert into discogs_access (timestamp) values (?)");
            $stmt->execute(array(time()));
        } catch (PDOException $e) {
            Util::log("Something went wrong when checking discogs access log: " . $e->getMessage(), true);
            die();
        }
        
        $ch = curl_init($uri);
        curl_setopt_array($ch, array(
            CURLOPT_HTTPHEADER => array("User-Agent: " . self::$userAgent),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true
        ));

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        $str_headers=explode("\r\n", $headers);
        array_shift($str_headers);    //get rid of "HTTP/1.1 200 OK"
        $resp_headers=array();
        foreach ($str_headers as $v) {
            if (strpos($v, ": ") !== false) {
                $v = explode(': ', $v, 2);
                $resp_headers[$v[0]] = $v[1];
            }
        }

        self::$lastCallTime = time();
        self::$callsLeft = $resp_headers["X-Discogs-Ratelimit-Remaining"];
        self::$maxCalls = $resp_headers["X-Discogs-Ratelimit"];

        return json_decode($body);
    }
}
