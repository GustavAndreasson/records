<?php
class Util {
    public static function log($txt, $error = false) {
        if ($error) {
            $file = LOGS_PATH . "ERROR.LOG";
        } else {
            $file = LOGS_PATH . "DEBUG.LOG";
        }
        file_put_contents($file, date("Y-m-d H:i:s") . ": " . $txt . "\n", FILE_APPEND | LOCK_EX);
    }
}
