<?php

require_once("../library/config.php");

if (isset($_REQUEST["action"])) {
    $action = $_REQUEST["action"];
    $main = new Main();
    //$collection = new Collection("gustav.andreasson");

    switch($action) {
    case "get_artist":
        getArtist($main);
        break;
    case "update_artist":
        updateArtist($main);
        break;
    default:
        break;
    }
}

function getArtist($main) {
    $artist_id = $_REQUEST["artist"];
    if (is_numeric($artist_id)) {
        echo json_encode($main->getArtist($artist_id));
    } else {
        echo "ERROR";
    }
}

function updateArtist($main) {
    $artist_id = $_REQUEST["artist"];
    if (is_numeric($artist_id)) {
        $artist = $main->getArtist($artist_id);
        $artist->update();
        echo json_encode($artist);
    } else {
        echo "ERROR";
    }
}
