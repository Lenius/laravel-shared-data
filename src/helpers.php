<?php

if (! function_exists('shared')) {
    /**
     * @return \Lenius\SharedData\SharedData
     */
    function shared()
    {
        return app(\Lenius\SharedData\SharedData::class);
    }
}

if (! function_exists('share')) {
    /**
     * @param array $args
     * @return \Lenius\SharedData\SharedData
     */
    function share(...$args)
    {
        return app(\Lenius\SharedData\SharedData::class)->put(...$args);
    }
}
