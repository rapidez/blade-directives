<?php

use Rapidez\BladeDirectives\OptionalDeep;

if (! function_exists('optionalDeep')) {
    function optionalDeep($value)
    {
        return new OptionalDeep($value);
    }
}
