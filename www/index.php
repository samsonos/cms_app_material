<?php
/**
 * SamsonCMS Init script
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */

// Build path to SamsonCMS
$cmsPath = realpath('../vendor/samsoncms/cms/www/');

if (file_exists($cmsPath)) {
    // Change document root
    $_SERVER['DOCUMENT_ROOT'] = $cmsPath;

    // Change cwd
    chdir($cmsPath);

    // Run SamsonCMS
    require $cmsPath.'/index.php';
} else {
    die('SamsonCMS is not installed');
}
