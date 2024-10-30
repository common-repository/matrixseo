<?php

/**
 * The MatrixSeo plugin bootstrap file.
 *
 * @link              https://www.matrixseo.ai
 * @since             1.0.0
 * @package           MatrixSeo
 *
 * @wordpress-plugin
 * Plugin Name:       MatrixSeo
 * Plugin URI:        https://www.matrixseo.ai
 * Description:       Increase organic traffic with SEO
 * Version:           1.0.10
 * Author:            MatrixSeo
 * Author URI:        https://www.matrixseo.ai
 */
if (! defined('WPINC')) {
    die();
}

require_once plugin_dir_path(__FILE__) . 'lib' . DIRECTORY_SEPARATOR . 'class-matrixseo-utils.php';

/**
 * The code that runs during the plugin activation.
 * This action is documented in includes/class-matrixseo-activator.php.
 *
 * @since 1.0.0
 * @param
 *            void
 * @return void
 */
function activate_MatrixSeo()
{
    require_once MatrixSeo_Utils::getBasePath('includes', 'class-matrixseo-activator.php');
    MatrixSeo_Activator::activate();
}

/**
 * The code that runs during the plugin deactivation.
 * This action is documented in includes/class-matrixseo-deactivator.php.
 *
 * @since 1.0.0
 * @param
 *            void
 * @return void
 */
function deactivate_MatrixSeo()
{
    require_once MatrixSeo_Utils::getBasePath('includes', 'class-matrixseo-deactivator.php');
    MatrixSeo_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_MatrixSeo');
register_deactivation_hook(__FILE__, 'deactivate_MatrixSeo');

require MatrixSeo_Utils::getBasePath('includes', 'class-matrixseo.php');

MatrixSeo_Utils::setTimezone();

/**
 * This plugin begins the execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 * @access public
 * @param
 *            void
 * @return void
 */
function run_MatrixSeo()
{
    $plugin = new MatrixSeo();
    $plugin->run();
}
run_MatrixSeo();
