<?php
/**
 * PHP wrapper to pre-load initial playground data, and output it as javascript.
 *
 * If necessary we could give this script a .js extension, and use the following in .htaccess to make Apache handle it with PHP:
 *
 * <FilesMatch "initial-data.js">
 *     AddHandler application/x-httpd-php5 .js
 * </FilesMatch>
 */

// Get the initial playground data
require_once '../server/bootstrap.php';
$app = require '../server/app.php';

$playgrounds = $app['playground_mapper']->getPlaygrounds();

header('Content-Type: text/javascript');
?>

var app = app || {};
app.initialPlaygroundData = <?php echo json_encode($playgrounds->toArray()) ?: 'null'; ?>;
