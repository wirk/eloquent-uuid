<?php

namespace WirksamesDesign\LaravelUuid\Database\Traits;

trait UuidBinaryHelpersTrait {

    /**
     * @param $value
     * @return int
     */
    public static function isUuid($value)
    {
        // credits to "Gambol" http://stackoverflow.com/a/13653180/1974291
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($uuidPattern, $value);
    }

    /**
     * Detects if a value is a binary uuid
     * @param $value
     * @param bool $assumeOptimized
     * @return bool
     */
    public static function isBinaryUuid($value, $assumeOptimized = false)
    {
        return (strlen($value) == 16)                                             //value is 16 bytes long
          && (!preg_match('//u', $value))                                         //value contains non-UTF-8 characters
          && self::isUuid(self::uuidBinaryToString($value, $assumeOptimized));    //value represents a Uuid after converting;
    }

    /**
     * @param $uuidString
     * @return string
     */
    public static function addUuidDashes($uuidString)
    {
        return
          substr($uuidString, 0, 8).'-'.
          substr($uuidString, 8, 4).'-'.
          substr($uuidString, 12, 4).'-'.
          substr($uuidString, 16, 4).'-'.
          substr($uuidString, 20);
    }

    /**
     * @param $uuidString
     * @return mixed
     */
    public static function removeUuidDashes($uuidString)
    {
        return str_replace('-', '', $uuidString);
    }

    /**
     * Convert a binary uuid to string
     * @param string $uuidBinary The binary-encoded uuid
     * @param bool $isOptimizedForSorting Whether the binary UUID has been re-arranged for sorting in the database
     * @return string
     */
    public static function uuidBinaryToString($uuidBinary, $isOptimizedForSorting = false)
    {
        if($isOptimizedForSorting) {
            $uuidString = bin2hex(substr($uuidBinary, 4, 4)) .
              bin2hex(substr($uuidBinary, 2, 2)) .
              bin2hex(substr($uuidBinary, 0, 2)) .
              bin2hex(substr($uuidBinary, 8, 2)) .
              bin2hex(substr($uuidBinary, 10));
        } else {
            $uuidString = bin2hex($uuidBinary);
        }
        $uuidString = self::addUuidDashes($uuidString);

        return $uuidString;
    }

    /**
     * Convert uuid string (with or without dashes) to binary
     * @param $uuidString
     * @param bool $optimizeForSorting Whether the bits should be re-arranged to allow sorting the table by the date part of the UUID
     * @return string The binary-encoded UUID
     */
    public function uuidStringToBinary($uuidString, $optimizeForSorting = false)
    {
        $uuidString = self::removeUuidDashes($uuidString);
        $isHexString = ctype_xdigit($uuidString);

        if($isHexString) {
            if($optimizeForSorting) {
                return hex2bin(substr($uuidString, 12, 4)) .
                  hex2bin(substr($uuidString, 8, 4)) .
                  hex2bin(substr($uuidString, 0, 8)) .
                  hex2bin(substr($uuidString, 16, 4)) .
                  hex2bin(substr($uuidString, 20));
            } else {
                return hex2bin($uuidString);
            }
        } elseif (self::isBinaryUuid($uuidString, $optimizeForSorting)) {
            return $uuidString;
        } else {
            return false;
        }
    }
}