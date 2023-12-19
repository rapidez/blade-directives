<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

if (! function_exists('data_get_value')) {
    /**
     * Get an item from an array or object using "dot" notation.
     * Executing functions along the way as well.
     *
     * @see https://github.com/laravel/framework/blob/78fa6f2c758e929233889103e2f709d0017c7598/src/Illuminate/Collections/helpers.php#L46
     * @param  mixed  $target
     * @param  string|array|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function data_get_value($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_iterable($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get_value($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = value($target[$segment]); // Result is wrapped in the value function
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = value($target->{$segment}); // Result is wrapped in the value function
            } elseif (is_object($target) && method_exists($target, $segment)) {
                $target = value($target->{$segment}()); // Added to execute methods if necessary
            } else {
                return value($default);
            }
        }

        return $target;
    }
}
