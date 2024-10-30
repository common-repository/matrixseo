<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    MatrixSeo
 * @subpackage MatrixSeo/includes
 * @author     MatrixSeo <support@matrixseo.ai>
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class MatrixSeo_Deactivator {
	
	/**
	 * Runned at the plugin deactivation.
	 * Unhooks the cronjobs and removes the actions.
	 * @since   1.0.0
	 * @param   void
     * @access  public
	 * @return  void
	 */
	public static function deactivate() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/class-matrixseo-reactor.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/class-matrixseo-utils.php';
		$reactor  = MatrixSeo_Reactor::getInstance();
		$utils=new MatrixSeo_Utils();
		wp_clear_scheduled_hook("matrixseocronjob");
		wp_clear_scheduled_hook("matrixseostopwords");
        MatrixSeo_Utils::cronDebug("Cronjobs deactivated", 3);
        remove_action('wp_loaded', array($reactor, 'detectAndSaveVisitor'));
        MatrixSeo_Utils::cronDebug("MatrixSeo plugin deactivated.", 3);
    }
}
