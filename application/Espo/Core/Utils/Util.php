<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils;

use Exception;
use RuntimeException;
use stdClass;

class Util
{
    /** @var string */
    protected static $separator = DIRECTORY_SEPARATOR;
    /** @var string[] */
    protected static $reservedWordList = ['Case'];

    /**
     * Get a folder separator.
     */
    public static function getSeparator(): string
    {
        return static::$separator;
    }

    /**
     * Convert camelCase to under_score.
     */
    public static function camelCaseToUnderscore(string $string): string
    {
        return static::toUnderScore($string);
    }

    /**
     * Convert hyphen-string to camelCase.
     */
    public static function hyphenToCamelCase(string $string): string
    {
        return self::toCamelCase($string, '-');
    }

    /**
     * Convert camelCase to hyphen-string.
     */
    public static function camelCaseToHyphen(string $string): string
    {
        return static::fromCamelCase($string, '-');
    }

    /**
     * Replace slashes and backslashes with a delimiter. E.g. Espo\Utils to Espo/Utils.
     *
     * @param string $name
     * @param string $delimiter
     * @return string
     */
    public static function toFormat(string $name, string $delimiter = '/'): string
    {
        /** @var string */
        return preg_replace("/[\/\\\]/", $delimiter, $name);
    }

    /**
     * Convert a string of a specific format to camelCase.
     *
     * @param string $input An input string.
     * @param string $symbol A formatting symbol.
     * @param bool $capitaliseFirstChar Capitalise the first character.
     * @return string
     */
    public static function toCamelCase($input, string $symbol = '_', bool $capitaliseFirstChar = false)
    {
        if (is_array($input)) { /** @phpstan-ignore-line */
            foreach ($input as &$value) {
                $value = static::toCamelCase($value, $symbol, $capitaliseFirstChar);
            }

            return $input; /** @phpstan-ignore-line */
        }

        $input = lcfirst($input);

        if ($capitaliseFirstChar) {
            $input = ucfirst($input);
        }

        /** @var string */
        return preg_replace_callback(
            '/' . $symbol . '([a-zA-Z])/',
            /**
             * @param string[] $matches
             */
            function ($matches): string {
                return strtoupper($matches[1]);
            },
            $input
        );
    }

    /**
     * Convert a string from camelCase to a format defined by a symbol.
     *
     * @param string $input
     * @return string
     */
    public static function fromCamelCase($input, string $symbol = '_')
    {
        if (is_array($input)) { /** @phpstan-ignore-line */
            foreach ($input as &$value) {
                $value = static::fromCamelCase($value, $symbol);
            }

            return $input; /** @phpstan-ignore-line */
        }

        $input[0] = strtolower($input[0]);

        /** @var string */
        return preg_replace_callback(
            '/([A-Z])/',
            function ($matches) use ($symbol) {
                return $symbol . strtolower($matches[1]);
            },
            $input
        );
    }

    /**
     * Convert a string from camelCase to under_score.
     *
     * @param string $input
     * @return string
     */
    public static function toUnderScore($input)
    {
        return static::fromCamelCase($input, '_');
    }

    /**
     * Merge arrays recursively. $newArray overrides $currentArray.
     *
     * @template T of array<int|string, mixed>
     * @param T $currentArray A source array.
     * @param T $newArray A merge-array (priority is same as for array_merge()).
     * @return T
     */
    public static function merge($currentArray, $newArray)
    {
        /** @phpstan-var mixed $currentArray */
        /** @phpstan-var mixed $newArray */

        $mergeIdentifier = '__APPEND__';

        if (is_array($currentArray) && !is_array($newArray)) {
            /** @var T */
            return $currentArray;
        } else if (!is_array($currentArray) && is_array($newArray)) {
            /** @var T */
            return $newArray; /** @phpstan-ignore-line */
        } else if (
            (!is_array($currentArray) || empty($currentArray)) &&
            (!is_array($newArray) || empty($newArray))
        ) {
            /** @var T */
            return [];
        }

        foreach ($newArray as $newName => $newValue) {
            if (
                is_array($newValue) &&
                array_key_exists($newName, $currentArray) &&
                is_array($currentArray[$newName])
            ) {

                // check __APPEND__ identifier
                $appendKey = array_search($mergeIdentifier, $newValue, true);

                if ($appendKey !== false) {
                    unset($newValue[$appendKey]);

                    $newValue = array_merge($currentArray[$newName], $newValue);
                } else if (
                    !static::isSingleArray($newValue) ||
                    !static::isSingleArray($currentArray[$newName])
                ) {
                    $newValue = static::merge($currentArray[$newName], $newValue);
                }

            }

            // check if exists __APPEND__ identifier and remove its
            if (!isset($currentArray[$newName]) && is_array($newValue)) {
                $newValue = static::unsetInArrayByValue($mergeIdentifier, $newValue);
            }

            $currentArray[$newName] = $newValue;
        }

        return $currentArray;
    }

    /**
     * Unset a value in an array recursively.
     *
     * @param string $needle A needle.
     * @param array<string|int, mixed> $haystack A haystack.
     * @return array<string|int, mixed>
     */
    public static function unsetInArrayByValue($needle, array $haystack, bool $reIndex = true): array
    {
        $doReindex = false;

        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = static::unsetInArrayByValue($needle, $value);
            } else if ($needle === $value) {
                unset($haystack[$key]);

                if ($reIndex) {
                    $doReindex = true;
                }
            }
        }

        if ($doReindex) {
            $haystack = array_values($haystack);
        }

        return $haystack;
    }

    /**
     * Get a full path of a  file.
     *
     * @param string|string[] $folderPath A folder path.
     */
    public static function concatPath($folderPath, ?string $filePath = null): string
    {
        if (is_array($folderPath)) {
            $fullPath = '';

            foreach ($folderPath as $path) {
                $fullPath = static::concatPath($fullPath, $path);
            }

            return static::fixPath($fullPath);
        }

        if (empty($filePath)) {
            return static::fixPath($folderPath);
        }

        if (empty($folderPath)) {
            return static::fixPath($filePath);
        }

        if (substr($folderPath, -1) == static::getSeparator() || str_ends_with($folderPath, '/')) {
            return static::fixPath($folderPath . $filePath);
        }

        return static::fixPath($folderPath) . static::getSeparator() . $filePath;
    }

    /**
     * Fix a path separator.
     */
    public static function fixPath(string $path): string
    {
        return str_replace('/', static::getSeparator(), $path);
    }

    /**
     * Convert an array to stdClass recursively.
     *
     * @param array<string, mixed> $array
     * @return stdClass
     */
    public static function arrayToObject($array)
    {
        /** @var stdClass */
        return self::arrayToObjectInternal($array);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function arrayToObjectInternal($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        // @todo Change to `array_is_list` when PHP 8.1 is the min supported.
        $isList = $value === array_values($value);

        $value =  array_map(fn($v) => self::arrayToObjectInternal($v), $value);

        if (!$isList) {
            $value = (object) $value;
        }

        return $value;
    }

    /**
     * Convert stdClass to array recursively.
     *
     * @param object $object
     * @return array<string, mixed>
     */
    public static function objectToArray($object)
    {
        /** @var array<string, mixed> */
        return self::objectToArrayInternal($object);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function objectToArrayInternal($value)
    {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            return array_map(fn($v) => self::objectToArrayInternal($v), $value);
        }

        return $value;
    }

    /**
     * Appends 'Obj' if a name is a PHP reserved  word.
     *
     * @param string $name A name.
     * @return string
     */
    public static function normalizeClassName($name)
    {
        if (in_array($name, self::$reservedWordList)) {
            $name .= 'Obj';
        }

        return $name;
    }

    /**
     * Remove 'Obj' if a name is a PHP reserved word.
     *
     * @param string $name
     * @return string
     */
    public static function normalizeScopeName($name)
    {
        foreach (self::$reservedWordList as $reservedWord) {
            if ($reservedWord.'Obj' == $name) {
                return $reservedWord;
            }
        }

        return $name;
    }

    /**
    * Get naming according to a prefix or postfix type.
    */
    public static function getNaming(
        string $name,
        string $prePostFix,
        string $type = 'prefix',
        string $symbol = '_'
    ): ?string {

        if ($type == 'prefix') {
            return static::toCamelCase($prePostFix.$symbol.$name, $symbol);
        }

        if ($type == 'postfix') {
            return static::toCamelCase($name.$symbol.$prePostFix, $symbol);
        }

        return null;
    }

    /**
     * Replace a search-string in an array recursively.
     *
     * @param string $search
     * @param string $replace
     * @param string[]|string $array
     * @param bool $isKeys
     * @return string|array<scalar, mixed>
     *
     * @todo Maybe to remove the method.
     * @deprecated
     */
    public static function replaceInArray($search = '', $replace = '', $array = [], $isKeys = true)
    {
        if (!is_array($array)) {
            return str_replace($search, $replace, $array);
        }

        $newArr = [];

        foreach ($array as $key => $value) {
            $addKey = $key;

            if ($isKeys) {
                $addKey = str_replace($search, $replace, $key);
            }

            $newArr[$addKey] = static::replaceInArray($search, $replace, $value, $isKeys);
        }

        return $newArr;
    }

    /**
     * Unset content items defined in the unset.json.
     *
     * @param array<string|int, mixed> $content
     * @param string|array<string|int, string|string[]> $unsets in format
     *  [
     *      'EntityType1' => [ 'unset1', 'unset2'],
     *      'EntityType2' => ['unset1', 'unset2'],
     *  ]
     *  OR
     *  ['EntityType1.unset1', 'EntityType2.unset2', ...]
     *  OR
     *  'EntityType1.unset1'
     * @param bool $unsetParentEmptyArray If unset empty parent array after unsets
     * @return array<string|int, mixed>
     */
    public static function unsetInArray(array $content, $unsets, bool $unsetParentEmptyArray = false)
    {
        if (empty($unsets)) {
            return $content;
        }

        if (is_string($unsets)) {
            $unsets = (array) $unsets;
        }

        foreach ($unsets as $rootKey => $unsetItem) {
            $unsetItem = is_array($unsetItem) ? $unsetItem : (array) $unsetItem;

            foreach ($unsetItem as $unsetString) {
                if (is_string($rootKey)) {
                    $unsetString = $rootKey . '.' . $unsetString;
                }

                $keyArr = explode('.', $unsetString);
                $keyChainCount = count($keyArr) - 1;

                $elem = &$content;

                $elementArr = [];
                $elementArr[] = &$elem;

                for ($i = 0; $i <= $keyChainCount; $i++) {
                    if (!array_key_exists($keyArr[$i], $elem)) {
                        continue;
                    }

                    if ($i == $keyChainCount) {
                        unset($elem[$keyArr[$i]]);

                        if ($unsetParentEmptyArray) {
                            for ($j = count($elementArr); $j > 0; $j--) {
                                $pointer =& $elementArr[$j];

                                if (empty($pointer)) {
                                    $previous =& $elementArr[$j - 1];

                                    unset($previous[$keyArr[$j - 1]]);
                                }
                            }
                        }
                    } else if (is_array($elem[$keyArr[$i]])) {
                        $elem = &$elem[$keyArr[$i]];

                        $elementArr[] = &$elem;
                    }
                }
            }
        }

        return $content;
    }


    /**
     * Get class name from a file path.
     *
     * @return class-string<object>
     */
    public static function getClassName(string $filePath): string
    {
        /** @var string $className */
        $className = preg_replace('/\.php$/i', '', $filePath);
        /** @var string $className */
        $className = preg_replace('/^(application|custom)(\/|\\\)/i', '', $className);
        /** @var class-string<object> */
        return static::toFormat($className, '\\');
    }

    /**
     * Get a value of a key.
     *
     * @param stdClass|array<string, mixed> $data
     * @param string[]|string $key Ex. of key is "entityDefs", "entityDefs.User".
     * @param mixed $default
     * @return mixed
     */
    public static function getValueByKey($data, $key = null, $default = null)
    {
        if (empty($key)) {
            return $data;
        }

        if (is_array($key)) {
            $keys = $key;
        } else {
            $keys = explode('.', $key);
        }

        $item = $data;

        foreach ($keys as $keyName) {
            if (is_array($item)) {
                if (isset($item[$keyName])) {
                    $item = $item[$keyName];
                } else {
                    return $default;
                }
            } else if (is_object($item)) {
                if (isset($item->$keyName)) {
                    $item = $item->$keyName;
                } else {
                    return $default;
                }
            }
        }

        return $item;
    }

    /**
     * Check if two variables are equal.
     *
     * @param mixed $var1
     * @param mixed $var2
     */
    public static function areEqual($var1, $var2): bool
    {
        if (is_array($var1)) {
            static::ksortRecursive($var1);
        }

        if (is_array($var2)) {
            static::ksortRecursive($var2);
        }

        return ($var1 === $var2);
    }

    /**
     * Sort array recursively.
     *
     * @param array<string|int, mixed> $array
     */
    public static function ksortRecursive(&$array): bool
    {
        if (!is_array($array)) {
            return false;
        }

        ksort($array);

        foreach ($array as $key => $value) {
            static::ksortRecursive($array[$key]);
        }

        return true;
    }

    /**
     * @param array<string|int, mixed> $array
     * @deprecated
     * @todo Make private.
     */
    public static function isSingleArray(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a UUID v4.
     */
    public static function generateUuid4(): string
    {
        try {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

            $hex = bin2hex($data);
        } catch (Exception) {
            throw new RuntimeException("Could not generate UUID.");
        }

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }

    /**
     * Generate a 17-character hex ID.
     */
    public static function generateId(): string
    {
        return uniqid() . substr(md5((string) rand()), 0, 4);
    }

    /**
     * Generate an ID with more entropy.
     */
    public static function generateMoreEntropyId(): string
    {
        return
            substr(md5(uniqid((string) rand(), true)), 0, 16) .
            substr(md5((string) rand()), 0, 4);
    }

    /**
     * Generate a crypt ID.
     *
     * @return string
     */
    public static function generateCryptId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate an API key.
     */
    public static function generateApiKey(): string
    {
        return self::generateCryptId();
    }

    /**
     * Generate a secret key.
     */
    public static function generateSecretKey(): string
    {
        return self::generateCryptId();
    }

    /**
     * Generate a 32-character hex key.
     */
    public static function generateKey(): string
    {
        return md5(uniqid((string) rand(), true));
    }

    /**
     * Sanitize a file name.
     */
    public static function sanitizeFileName(string $fileName): string
    {
        /** @var string */
        return preg_replace("/([^\w\s\d\-_~,;:\[\]\(\).])/u", '_', $fileName);
    }

    /**
     * Improved computing the difference of arrays.
     *
     * @deprecated As of v7.4.
     * @param array<string|int, mixed> $array1
     * @param array<string|int, mixed> $array2
     * @return array<string|int, mixed>
     */
    public static function arrayDiff(array $array1, array $array2)
    {
        $diff = [];

        foreach ($array1 as $key1 => $value1) {
            if (array_key_exists($key1, $array2)) {
                if ($value1 !== $array2[$key1]) {
                    $diff[$key1] = $array2[$key1];
                }

                continue;
            }

            $diff[$key1] = $value1;
        }

        return array_merge($diff, array_diff_key($array2, $array1));
    }

    /**
     * Fill an array with specific keys.
     *
     * @param mixed[]|mixed $keys
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    public static function fillArrayKeys($keys, $value)
    {
        $arrayKeys = is_array($keys) ? $keys : explode('.', $keys);

        $array = [];

        foreach (array_reverse($arrayKeys) as $i => $key) {
            $array = [
                $key => ($i == 0) ? $value : $array,
            ];
        }

        return $array;
    }

    /**
     * Array keys exists.
     *
     * @param mixed[] $keys
     * @param array<string|int, mixed> $array
     * @return bool
     */
    public static function arrayKeysExists(array $keys, array $array)
    {
       return !array_diff_key(array_flip($keys), $array);
    }

    /**
     * Convert a numeric a memory amount value to a number of bytes it represents. E.g. 1K => 1024.
     */
    public static function convertToByte(string $value): int
    {
        $valueTrimmed = trim($value);
        $last = strtoupper(substr($valueTrimmed, -1));

        return match ($last) {
            'G' => (int) $valueTrimmed * 1024 * 1024 * 1024 ,
            'M' => (int) $valueTrimmed * 1024 * 1024,
            'K' => (int) $valueTrimmed * 1024,
            default => (int) $valueTrimmed,
        };
    }

    /**
     * Check whether values are equal.
     *
     * @param mixed $v1
     * @param mixed $v2
     */
    public static function areValuesEqual($v1, $v2, bool $isUnordered = false): bool
    {
        if (is_array($v1) && is_array($v2)) {
            if ($isUnordered) {
                sort($v1);
                sort($v2);
            }

            if ($v1 != $v2) {
                return false;
            }

            foreach ($v1 as $i => $itemValue) {
                if (is_object($itemValue) && is_object($v2[$i])) {
                    if (!self::areValuesEqual($itemValue, $v2[$i])) {
                        return false;
                    }

                    continue;
                }

                if ($itemValue !== $v2[$i]) {
                    return false;
                }
            }

            return true;
        }

        if (is_object($v1) && is_object($v2)) {
            if ($v1 != $v2) {
                return false;
            }

            $a1 = get_object_vars($v1);
            $a2 = get_object_vars($v2);

            foreach ($a1 as $key => $itemValue) {
                if (is_object($itemValue) && is_object($a2[$key])) {
                    if (!self::areValuesEqual($itemValue, $a2[$key])) {
                        return false;
                    }

                    continue;
                }

                if (is_array($itemValue) && is_array($a2[$key])) {
                    if (!self::areValuesEqual($itemValue, $a2[$key])) {
                        return false;
                    }

                    continue;
                }

                if ($itemValue !== $a2[$key]) {
                    return false;
                }
            }

            return true;
        }

        return $v1 === $v2;
    }

    /**
     * Upper-case a first letter for multibyte strings.
     */
    public static function mbUpperCaseFirst(string $string): string
    {
        if (!$string) {
            return $string;
        }

        $length = mb_strlen($string);
        $firstChar = mb_substr($string, 0, 1);
        $then = mb_substr($string, 1, $length - 1);

        return mb_strtoupper($firstChar) . $then;
    }

    /**
     * Lower-case a first letter for multibyte strings.
     */
    public static function mbLowerCaseFirst(string $string): string
    {
        if (!$string) {
            return $string;
        }

        $length = mb_strlen($string);
        $firstChar = mb_substr($string, 0, 1);
        $then = mb_substr($string, 1, $length - 1);

        return mb_strtolower($firstChar) . $then;
    }

    /**
     * Sanitize HTML code.
     *
     * @param string|string[] $text A source.
     * @param string[] $permittedHtmlTags Allows only HTML tags without parameters like <p></p>, <br>, etc.
     * @return string|string[]
     */
    public static function sanitizeHtml($text, $permittedHtmlTags = ['p', 'br', 'b', 'strong', 'pre'])
    {
        if (is_array($text)) {
            foreach ($text as $key => &$value) {
                $value = self::sanitizeHtml($value, $permittedHtmlTags);
            }

            return $text;
        }

        $sanitized = htmlspecialchars($text, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        foreach ($permittedHtmlTags as $htmlTag) {
            /** @var string $sanitized */
            $sanitized = preg_replace('/&lt;(\/)?(' . $htmlTag . ')&gt;/i', '<$1$2>', $sanitized);
        }

        return $sanitized;
    }

    /**
     * @deprecated As of v7.4.
     * @param mixed $paramValue
     */
    public static function urlAddParam(string $url, string $paramName, $paramValue): string
    {
        $urlQuery = parse_url($url, \PHP_URL_QUERY);

        if (!$urlQuery) {
            $params = [
                $paramName => $paramValue
            ];

            $url = trim($url);
            /** @var string $url */
            $url = preg_replace('/\/\?$/', '', $url);
            /** @var string $url */
            $url = preg_replace('/\/$/', '', $url);

            return $url . '/?' . http_build_query($params);
        }

        parse_str($urlQuery, $params);

        if (!isset($params[$paramName]) || $params[$paramName] != $paramValue) {
            $params[$paramName] = $paramValue;

            return str_replace($urlQuery, http_build_query($params), $url);
        }

        return $url;
    }

    /**
     * @deprecated As of v7.4.
     */
    public static function urlRemoveParam(string $url, string $paramName, string $suffix = ''): string
    {
        $urlQuery = parse_url($url, \PHP_URL_QUERY);

        if ($urlQuery) {
            parse_str($urlQuery, $params);

            if (isset($params[$paramName])) {
                unset($params[$paramName]);

                $newUrl = str_replace($urlQuery, http_build_query($params), $url);

                if (empty($params)) {
                    /** @var string $newUrl */
                    $newUrl = preg_replace('/\/\?$/', '', $newUrl);
                    /** @var string $newUrl */
                    $newUrl = preg_replace('/\/$/', '', $newUrl);

                    $newUrl .= $suffix;
                }

                return $newUrl;
            }
        }

        return $url;
    }

    /**
     * Generate a password.
     *
     * @param int $length A length.
     * @param int $letters A number of letters.
     * @param int $digits A number of digits.
     * @param bool $bothCases Use upper and lower case letters.
     */
    public static function generatePassword(
        int $length = 8,
        int $letters = 5,
        int $digits = 3,
        bool $bothCases = false,
        int $specialCharacters = 0
    ): string {

        $chars = [
            'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
            '0123456789',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'abcdefghijklmnopqrstuvwxyz',
            "'-!\"#$%&()*,./:;?@[]^_`{|}~+<=>",
        ];

        $shuffle = function ($array) {
            $currentIndex = count($array);

            while (0 !== $currentIndex) {
                $rand = (0 + (1 - 0) * (mt_rand() / mt_getrandmax()));
                $randomIndex = intval(floor($rand * $currentIndex));
                $currentIndex -= 1;
                $temporaryValue = $array[$currentIndex];

                $array[$currentIndex] = $array[$randomIndex];
                $array[$randomIndex] = $temporaryValue;
            }

            return $array;
        };

        $upperCase = 0;
        $lowerCase = 0;

        if ($bothCases) {
            $upperCase = 1;
            $lowerCase = 1;

            if ($letters >= 2) {
                $letters = $letters - 2;
            } else {
                $letters = 0;
            }
        }

        $either = $length - ($letters + $digits + $upperCase + $lowerCase + $specialCharacters);

        if ($either < 0) {
            $either = 0;
        }

        $array = [];

        foreach ([$letters, $digits, $either, $upperCase, $lowerCase, $specialCharacters] as $i => $len) {
            $set = $chars[$i];
            $subArray = [];

            $j = 0;

            while ($j < $len) {
                $rand = (0 + (1 - 0) * (mt_rand() / mt_getrandmax()));
                $index = intval(floor($rand * strlen($set)));
                $subArray[] = $set[$index];
                $j++;
            }

            $array = array_merge($array, $subArray);
        }

        return implode('', $shuffle($array));
    }

    /**
     * @deprecated Use `normalizeScopeName`.
     *
     * @param string $name
     * @return string
     */
    public static function normilizeScopeName($name)
    {
        return self::normalizeScopeName($name);
    }

    /**
     * @deprecated Use `normalizeClassName`.
     *
     * @param string $name
     * @return string
     */
    public static function normilizeClassName($name)
    {
        return self::normalizeClassName($name);
    }
}
