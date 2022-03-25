<?php

require_once(__DIR__ . '/../vendor/autoload.php');

// Set an error handler to prevent error ignored.
set_error_handler(
    function ($errno, $message, $filename = null, $line = null, $_ = null)
    {
        throw new ErrorException($message, $errno, 1, $filename, $line);
    }
);
