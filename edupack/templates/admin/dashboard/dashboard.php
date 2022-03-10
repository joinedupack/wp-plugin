<?php
/**
 * The dashboard file
 *
 * @package edupack
 */

// Title : Filename.
// (do no include .php as this is used in GET request, we add this on later.)
$tabs = array(
	'Reports'  => 'reports',
	'My Sites' => 'my-sites',
);

// The current tab, if not in request use default passed in factory
$tab = $_GET['tab'] ?? '';

$header = new Edupack_Settings_Header(
	$tabs,
	'dashboard',
	'/dashboard/tabs/',
	$tab,
	'reports'
);
$header->header();
$header->output_template();
