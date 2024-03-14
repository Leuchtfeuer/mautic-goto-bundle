<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Helper;

abstract class BasicEnum
{
    /**
     * @var mixed[]|null
     */
    private static ?array $constCacheArray = null;

    /**
     * @return mixed[]
     *
     * @throws \ReflectionException
     */
    private static function getConstants(): array
    {
        if (null === self::$constCacheArray) {
            self::$constCacheArray = [];
        }

        $calledClass = static::class;
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect                             = new \ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }

        return self::$constCacheArray[$calledClass];
    }

    public static function isValidName(string $name, bool $strict = false): bool
    {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));

        return in_array(strtolower($name), $keys, true);
    }

    /**
     * @param mixed $value
     */
    public static function isValidValue($value, bool $strict = true): bool
    {
        $values = array_values(self::getConstants());

        return in_array($value, $values, $strict);
    }

    /**
     * @return mixed[]
     */
    public static function toArray(): array
    {
        return array_values(self::getConstants());
    }

    /**
     * @return mixed[]
     */
    public static function toArrayOfNames(): array
    {
        return array_keys(self::getConstants());
    }

    /**
     * @return mixed[]
     */
    public static function getKeyPairs(): array
    {
        $a = self::getConstants();

        return array_combine($a, $a);
    }
}
