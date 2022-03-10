<?php
/**
 * Edupack governance dashboard
 *
 * @package edupack
 * @version 1.0.0
 */

// Title : Filename 
// (do no include .php as this is used in GET request, we add this on later.)
$tabs = array(
	'Archival Policies' => 'archival-policies',
	'Self-service'      => 'self-service',
	'Brand'             => 'brand',
	'Blocks & Patterns' => 'blocks-policies',
	'Integrations'      => 'integrations',
);

// The current tab, if not in request use default passed in factory
$tab = $_GET['tab'] ?? '';

$header = new Edupack_Settings_Header(
	$tabs,
	'governance',
	'/governance/tabs/',
	$tab,
	'archival-policies'
);
$header->header();
$header->output_template();

?>