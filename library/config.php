<?php
defined("LIBRARY_PATH")
    or define("LIBRARY_PATH", dirname(__FILE__) . '/');
defined("TEMPLATES_PATH")
    or define("TEMPLATES_PATH", dirname(__FILE__) . '/../templates/');
defined("LOGS_PATH")
    or define("LOGS_PATH", dirname(__FILE__) . '/../var/logs/');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined("DISCOGS_BASE_URL") or define("DISCOGS_BASE_URL", "https://api.discogs.com");
defined("DISCOGS_KEY") or define("DISCOGS_KEY", "AbSDVeRGEvphNcvWDAMJ");
defined("DISCOGS_SECRET") or define("DISCOGS_SECRET", "LeguGhdInJDIiMNxyVEzQSHhWlOTCvNR");

defined("DB_SERVERNAME") or define("DB_SERVERNAME", "localhost");
defined("DB_USERNAME") or define("DB_USERNAME", "records");
defined("DB_PASSWORD") or define("DB_PASSWORD", "recordspwd");
defined("DB_NAME") or define("DB_NAME", "records");

require_once("Util.php");
require_once("Discogs.php");
require_once("Track.php");
require_once("Record.php");
require_once("Collection.php");
require_once("Main.php");
