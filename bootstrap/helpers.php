<?php

/*
|--------------------------------------------------------------------------
| Global Helper Functions
|--------------------------------------------------------------------------
|
| Here you can define some functions that will be auto-loaded and hence
| available anywhere in your app.
|
*/

/**
 * Get the requested records per page count, while limiting between a minimum
 * and maximum value.
 *
 * @param int $desired
 * @param int $min
 * @param int $max
 * @return int
 */
function per_page($desired = null, $min = 15, $max = 100)
{
    $n = $desired ?? request()->input('perPage', 15); // Fallback to the request or 15 if not specified
    return $n < $min ? $min : ($n > $max ? $max : $n);
}
