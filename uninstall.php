<?php

/**
 * Fired when the plugin is uninstalled. 
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * @link       http://matrixseo.com
 * @since      1.0.0
 *
 * @package    MatrixSeo
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$path = plugin_dir_path( __FILE__ ) .'includes'. DIRECTORY_SEPARATOR .'class-matrixseo-uninstaller.php';
require_once str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
MatrixSeo_Uninstaller::uninstall();