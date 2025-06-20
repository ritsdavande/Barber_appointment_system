<?php
/**
 * PHPMailer autoloader
 */
function PHPMailerAutoload($classname)
{
    // Don't load if using built-in SMTP - not needed for our implementation
    if (version_compare(PHP_VERSION, '5.6.0', '>=') and is_subclass_of($classname, 'PHPMailer')) {
        return;
    }
    
    $filename = dirname(__FILE__). DIRECTORY_SEPARATOR . 'class.'. strtolower($classname) . '.php';
    if (is_readable($filename)) {
        require $filename;
    }
}

// Register the autoloader - we only use spl_autoload_register now, not __autoload
if (version_compare(PHP_VERSION, '5.1.2', '>=')) {
    // SPL autoloading was introduced in PHP 5.1.2
    if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
        spl_autoload_register('PHPMailerAutoload', true, true);
    } else {
        spl_autoload_register('PHPMailerAutoload');
    }
}
// We don't use __autoload anymore - it's deprecated in PHP 7.2+ 