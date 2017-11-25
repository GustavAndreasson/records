<?php

require_once("../library/config.php");

if (isset($_REQUEST["action"])) {
    $action = $_REQUEST["action"];
    $main = new Main();
    //$collection = new Collection("gustav.andreasson");
    
    switch($action) {
    case "set_collection":
        setCollection($main);
        break;
    case "get_collection":
        getCollection($main->collection);
        break;
    case "update_collection":
        updateCollection($main->collection);
        break;
    default:
        break;
    }
}

function setCollection($main) {
    $user = $_REQUEST["user"];
    $main->setCollection($user);
}

function getCollection($collection) {
    if ($collection) {
        echo json_encode($collection->getCollection());
    } else {
        echo "no collection";
    }
}

function updateCollection($collection) {
    if ($collection) {
        $page = $_REQUEST["page"];
        $pageSize = $_REQUEST["page_size"];
        echo json_encode($collection->updateCollection($page, $pageSize));
    } else {
        echo "no collection";
    }
}
