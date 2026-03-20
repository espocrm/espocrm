<?php

namespace Espo\Core\Htmlizer\Patch;

use DevTheorem\Handlebars\Handlebars;
use DevTheorem\Handlebars\HelperOptions;
use DevTheorem\Handlebars\RuntimeContext;
use DevTheorem\Handlebars\SafeString;
use DevTheorem\Handlebars\StringObject;

// Patched the `raw` method by adding the float type.

final class Runtime
{
    /**
     * Output debug info.
     */
    public static function debug(string $expression, string $runtimeFn, mixed ...$rest): mixed
    {
        $runtime = self::class;
        return call_user_func_array("$runtime::$runtimeFn", $rest);
    }

    /**
     * Throw exception for missing expression. Only used in strict mode.
     */
    public static function miss(string $v): void
    {
        throw new \Exception("Runtime: $v does not exist");
    }

    /**
     * For {{log}}.
     * @param array<mixed> $v
     */
    public static function lo(array $v): string
    {
        error_log(var_export($v[0], true));
        return '';
    }

    /**
     * For {{#if}} and {{#unless}}.
     *
     * @param array<array<mixed>|string|int>|string|\Stringable|int|float|bool|null $v value to be tested
     * @param bool $zero include zero as true
     *
     * @return bool Return true when the value is not null nor false.
     */
    public static function ifvar(mixed $v, bool $zero = false): bool
    {
        return $v !== null
            && $v !== false
            && ($zero || ($v !== 0 && $v !== 0.0))
            && $v !== ''
            && (!$v instanceof \Stringable || (string) $v !== '')
            && (!is_array($v) || count($v) > 0);
    }

    /**
     * For {{^var}} .
     *
     * @param array<array<mixed>|string|int>|string|int|bool|null $v value to be tested
     *
     * @return bool Return true when the value is null or false or empty
     */
    public static function isec(mixed $v): bool
    {
        return $v === null || $v === false || (is_array($v) && count($v) === 0);
    }

    /**
     * HTML encode {{var}} just like handlebars.js
     *
     * @param array<array<mixed>|string|int>|string|SafeString|int|null $var value to be htmlencoded
     */
    public static function encq($var): string
    {
        if ($var instanceof SafeString) {
            return (string) $var;
        }

        return Handlebars::escapeExpression(static::raw($var));
    }

    /**
     * Espo comment: Added the float type.
     *
     * Get string value
     *
     * @param array<array<mixed>|string|int>|string|int|bool|null $v value to be output
     * @param int $ex 1 to return untouched value, default is 0
     *
     * @return array<array<mixed>|string|int>|string|null The raw value of the specified variable
     */
    public static function raw(array|string|int|float|bool|null $v, int $ex = 0): string|array|null
    {
        if ($ex) {
            return $v;
        }

        if ($v === true) {
            return 'true';
        }

        if ($v === false) {
            return 'false';
        }

        if (is_array($v)) {
            if (count(array_diff_key($v, array_keys(array_keys($v)))) > 0) {
                return '[object Object]';
            } else {
                $ret = '';
                foreach ($v as $vv) {
                    $ret .= static::raw($vv) . ',';
                }
                return substr($ret, 0, -1);
            }
        }

        return "$v";
    }

    /**
     * For {{#var}} or {{#each}} .
     *
     * @param array<array<mixed>|string|int>|string|int|bool|null|\Traversable<string, mixed> $v value for the section
     * @param array<string>|null $bp block parameters
     * @param array<array<mixed>|string|int>|string|int|null $in input data with current scope
     * @param bool $each true when rendering #each
     * @param \Closure $cb callback function to render child context
     * @param \Closure|null $else callback function to render child context when {{else}}
     */
    public static function sec(RuntimeContext $cx, mixed $v, ?array $bp, mixed $in, bool $each, \Closure $cb, ?\Closure $else = null): string
    {
        $push = $in !== $v || $each;

        $isAry = is_array($v) || ($v instanceof \ArrayObject);
        $isTrav = $v instanceof \Traversable;
        $loop = $each;
        $keys = null;
        $last = null;
        $isObj = false;

        if ($isAry && $else !== null && count($v) === 0) {
            return $else($cx, $in);
        }

        // #var, detect input type is object or not
        if (!$loop && $isAry) {
            $keys = array_keys($v);
            $loop = (count(array_diff_key($v, array_keys($keys))) == 0);
            $isObj = !$loop;
        }

        if (($loop && $isAry) || $isTrav) {
            if ($each && !$isTrav) {
                // Detect input type is object or not when never done once
                if ($keys == null) {
                    $keys = array_keys($v);
                    $isObj = (count(array_diff_key($v, array_keys($keys))) > 0);
                }
            }
            $ret = [];
            $cx = clone $cx;
            if ($push) {
                $cx->scopes[] = $in;
            }
            $i = 0;
            $oldSpvar = $cx->spVars ?? [];
            $cx->spVars = array_merge(['root' => $oldSpvar['root'] ?? null], $oldSpvar, ['_parent' => $oldSpvar]);
            if (!$isTrav) {
                $last = count($keys) - 1;
            }

            $isSparceArray = $isObj && (count(array_filter(array_keys($v), 'is_string')) == 0);

            foreach ($v as $index => $raw) {
                $cx->spVars['first'] = ($i === 0);
                $cx->spVars['last'] = ($i == $last);
                $cx->spVars['key'] = $index;
                $cx->spVars['index'] = $isSparceArray ? $index : $i;
                $i++;
                if (isset($bp[0])) {
                    $raw = static::merge($raw, [$bp[0] => $raw]);
                }
                if (isset($bp[1])) {
                    $raw = static::merge($raw, [$bp[1] => $index]);
                }
                $ret[] = $cb($cx, $raw);
            }

            if ($isObj) {
                unset($cx->spVars['key']);
            } else {
                unset($cx->spVars['last']);
            }
            unset($cx->spVars['index'], $cx->spVars['first']);

            if ($push) {
                array_pop($cx->scopes);
            }
            return join('', $ret);
        }

        if ($each) {
            return ($else !== null) ? $else($cx, $in) : '';
        }

        if ($isAry) {
            if ($push) {
                $cx->scopes[] = $in;
            }
            $ret = $cb($cx, $v);
            if ($push) {
                array_pop($cx->scopes);
            }
            return $ret;
        }

        if ($v === true) {
            return $cb($cx, $in);
        }

        if ($v !== null && $v !== false) {
            return $cb($cx, $v);
        }

        if ($else !== null) {
            return $else($cx, $in);
        }

        return '';
    }

    /**
     * For {{#with}} .
     *
     * @param array<array<mixed>|string|int>|string|int|bool|null $v value to be the new context
     * @param array<string>|null $bp block parameters
     * @param array<array<mixed>|string|int>|\stdClass|null $in input data with current scope
     * @param \Closure $cb callback function to render child context
     * @param \Closure|null $else callback function to render child context when {{else}}
     */
    public static function wi(RuntimeContext $cx, mixed $v, ?array $bp, array|\stdClass|null $in, \Closure $cb, ?\Closure $else = null): string
    {
        if (isset($bp[0])) {
            $v = static::merge($v, [$bp[0] => $v]);
        }

        if ($v === false || $v === null || (is_array($v) && count($v) === 0)) {
            return $else ? $else($cx, $in) : '';
        }

        if ($v === $in) {
            $ret = $cb($cx, $v);
        } else {
            $cx->scopes[] = $in;
            $ret = $cb($cx, $v);
            array_pop($cx->scopes);
        }

        return $ret;
    }

    /**
     * Get merged context.
     *
     * @param array<array<mixed>|string|int>|object|string|int|null $a the context to be merged
     * @param array<array<mixed>|string|int>|string|int|null $b the new context to overwrite
     *
     * @return array<array<mixed>|string|int>|string|int the merged context object
     */
    public static function merge(mixed $a, mixed $b): mixed
    {
        if (is_array($b)) {
            if ($a === null) {
                return $b;
            } elseif (is_array($a)) {
                return array_merge($a, $b);
            } else {
                if (!is_object($a)) {
                    $a = new StringObject($a);
                }
                foreach ($b as $i => $v) {
                    $a->$i = $v;
                }
            }
        }
        return $a;
    }

    /**
     * For {{> partial}} .
     *
     * @param string $p partial name
     * @param array<array<mixed>|string|int>|string|int|null $v value to be the new context
     */
    public static function p(RuntimeContext $cx, string $p, $v, int $pid, string $sp): string
    {
        $pp = ($p === '@partial-block') ? $p . ($pid > 0 ? $pid : $cx->partialId) : $p;

        if (!isset($cx->partials[$pp])) {
            throw new \Exception("Runtime: the partial $p could not be found");
        }

        $cx = clone $cx;
        $cx->partialId = ($p === '@partial-block') ? ($pid > 0 ? $pid : ($cx->partialId > 0 ? $cx->partialId - 1 : 0)) : $pid;

        return $cx->partials[$pp]($cx, static::merge($v[0][0], $v[1]), $sp);
    }

    /**
     * For {{#* inlinepartial}} .
     *
     * @param string $p partial name
     * @param \Closure $code the compiled partial code
     */
    public static function in(RuntimeContext $cx, string $p, \Closure $code): void
    {
        $cx->partials[$p] = $code;
    }

    /**
     * For single custom helpers.
     *
     * @param string $ch the name of custom helper to be executed
     * @param array<array<mixed>|string|int> $vars variables for the helper
     * @param array<string,array<mixed>|string|int> $_this current rendering context for the helper
     */
    public static function hbch(RuntimeContext $cx, string $ch, array $vars, mixed &$_this): mixed
    {
        if (isset($cx->blParam[0][$ch])) {
            return $cx->blParam[0][$ch];
        }

        $options = new HelperOptions(
            name: $ch,
            hash: $vars[1],
            fn: function () { return ''; },
            inverse: function () { return ''; },
            blockParams: 0,
            scope: $_this,
            data: $cx->spVars,
        );

        return static::exch($cx, $ch, $vars, $options);
    }

    /**
     * For block custom helpers.
     *
     * @param string $ch the name of custom helper to be executed
     * @param array<array<mixed>|string|int> $vars variables for the helper
     * @param array<string,array<mixed>|string|int> $_this current rendering context for the helper
     * @param bool $inverted the logic will be inverted
     * @param \Closure|null $cb callback function to render child context
     * @param \Closure|null $else callback function to render child context when {{else}}
     */
    public static function hbbch(RuntimeContext $cx, string $ch, array $vars, mixed &$_this, bool $inverted, ?\Closure $cb, ?\Closure $else = null): mixed
    {
        $blockParams = 0;
        $data = &$cx->spVars;

        if (isset($vars[2])) {
            $blockParams = count($vars[2]);
        }

        // invert the logic
        if ($inverted) {
            $tmp = $else;
            $else = $cb;
            $cb = $tmp;
        }

        $fn = function ($context = null, $data = null) use ($cx, $_this, $cb, $vars) {
            $cx = clone $cx;
            $old_spvar = $cx->spVars;
            if (isset($data['data'])) {
                $cx->spVars = array_merge(['root' => $old_spvar['root']], $data['data'], ['_parent' => $old_spvar]);
            }

            $ex = false;
            if (isset($data['blockParams'], $vars[2])) {
                $ex = array_combine($vars[2], array_slice($data['blockParams'], 0, count($vars[2])));
                array_unshift($cx->blParam, $ex);
            } elseif (isset($cx->blParam[0])) {
                $ex = $cx->blParam[0];
            }

            if ($context === null) {
                $ret = $cb($cx, is_array($ex) ? static::merge($_this, $ex) : $_this);
            } else {
                $cx->scopes[] = $_this;
                $ret = $cb($cx, is_array($ex) ? static::merge($context, $ex) : $context);
                array_pop($cx->scopes);
            }

            if (isset($data['data'])) {
                $cx->spVars = $old_spvar;
            }
            return $ret;
        };

        if ($else) {
            $inverse = function ($context = null) use ($cx, $_this, $else) {
                if ($context === null) {
                    $ret = $else($cx, $_this);
                } else {
                    $cx->scopes[] = $_this;
                    $ret = $else($cx, $context);
                    array_pop($cx->scopes);
                }
                return $ret;
            };
        } else {
            $inverse = function () {
                return '';
            };
        }

        $options = new HelperOptions(
            name: $ch,
            hash: $vars[1],
            fn: $fn,
            inverse: $inverse,
            blockParams: $blockParams,
            scope: $_this,
            data: $data,
        );

        return static::exch($cx, $ch, $vars, $options);
    }

    /**
     * Execute custom helper with prepared options
     *
     * @param string $ch the name of custom helper to be executed
     * @param array<array<mixed>|string|int> $vars variables for the helper
     */
    public static function exch(RuntimeContext $cx, string $ch, array $vars, HelperOptions $options): mixed
    {
        $args = $vars[0];
        $args[] = $options;

        try {
            return ($cx->helpers[$ch])(...$args);
        } catch (\Throwable $e) {
            throw new \Exception("Runtime: call custom helper '$ch' error: " . $e->getMessage());
        }
    }
}
