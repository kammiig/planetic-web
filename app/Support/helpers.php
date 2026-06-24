<?php

use App\Models\SiteSetting;

if (! function_exists('setting')) {
    /**
     * Read an admin-editable site setting (type-cast), falling back to the
     * given default when the key is missing or blank. Cached on the hot path.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return SiteSetting::get($key, $default);
    }
}
