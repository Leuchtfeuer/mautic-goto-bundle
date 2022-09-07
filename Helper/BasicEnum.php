<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\Helper;

use ReflectionClass;

abstract class BasicEnum
{
    private static $constCacheArray;

    private static function getConstants()
    {
        if (null === self::$constCacheArray) {
            self::$constCacheArray = [];
        }

        $calledClass = static::class;
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect                             = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }

        return self::$constCacheArray[$calledClass];
    }

    /**
     * @param $name
     * @param bool $strict
     *
     * @return bool
     */
    public static function isValidName($name, $strict = false)
    {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));

        return in_array(strtolower($name), $keys, true);
    }

    /**
     * @param $value
     * @param bool $strict
     *
     * @return bool
     */
    public static function isValidValue($value, $strict = true)
    {
        $values = array_values(self::getConstants());

        return in_array($value, $values, $strict);
    }

    /**
     * @return array
     */
    public static function toArray()
    {
        return array_values(self::getConstants());
    }

    /**
     * @return array
     */
    public static function toArrayOfNames()
    {
        return array_keys(self::getConstants());
    }

    /**
     * @return array
     */
    public static function getKeyPairs()
    {
        $a = self::getConstants();
        /*foreach ($a as $key => $constant){
            $name = 'plugin.citrix.product.' . $constant;
        }*/
        return array_combine($a, $a);
    }
}
