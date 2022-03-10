<?php
/**
 * Edupack Updates
 *
 * Adds the ability to get remote updates
 *
 * @package WordPress
 */
edupack_global_vars();

require $GLOBALS['edupack_variables']['path'] . 'vendor/autoload.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://kernl.us/api/v1/updates/605f636fbd1d9ceb1c90585c/',
    $GLOBALS['edupack_variables']['path'] . 'edupack.php',
    'edupack'
);
