<?php

defined('ABSPATH') || exit;

function sx_asset_url(string $path): string
{
	return get_theme_file_uri('assets/' . $path);
}

function sx_asset_version(string $path): string
{
	$file = SIDEX_DIR . '/assets/' . $path;
	return is_file($file) ? (string) filemtime($file) : '0';
}

/** Langue WPML courante (fr par défaut). */
function sx_lang(): string
{
	return (string) apply_filters('wpml_current_language', 'fr');
}
