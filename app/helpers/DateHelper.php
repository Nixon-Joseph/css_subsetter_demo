<?php
class DateHelper {
    public static function UTCNow(): string {
        return gmdate("Y-m-d H:i:s");
    }
    public static function ServerNow(): string {
        return date("Y-m-d H:i:s");
    }
}
?>