<?php
/**
 * Fired during plugin uninstall.
 *
 * This class defines all code necessary to run during the plugin's uninstall.
 *
 * @since      1.0.0
 * @package    MatrixSeo
 * @subpackage MatrixSeo/includes
 * @author     MatrixSeo <support@matrixseo.ai>
 */

class MatrixSeo_Uninstaller {

    /**
     * This uninstall function deletes the created tables and options that we had set on the activation.
     * @since   1.0.0
     * @access  public
     * @param   void
     * @return  void
     */
    public static function uninstall() {
        require_once dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class-matrixseo-config.php';

        $networkActive=function_exists("get_sites");

        if($networkActive){
            self::dropDBTablesNetwork();
            MatrixSeo_Config::unsetNetworkDefaults();
        }
        else{
            self::dropDBTables();
            MatrixSeo_Config::unsetDefaults();

        }

        self::rrmdir(WP_CONTENT_DIR.DIRECTORY_SEPARATOR."matrixseo");

    }

    /**
     * @since   1.0.0
     * @access  public
     * @param   void
     * @return  void
     */
    public static function dropDBTables(){
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."mx_seo_actions");
        $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."mx_seo_urls");
        $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."mx_seo_ignore");
        $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."mx_seo_history");
    }

    /**
     * @since   1.0.7
     * @access  public
     * @param   void
     * @return  void
     */
    public static function dropDBTablesNetwork(){
        global $wpdb;
        $networkSites=get_sites();
        if(is_array($networkSites)) {
            foreach ($networkSites as $networkSite) {
                $prefix = $wpdb->get_blog_prefix($networkSite->blog_id);
                $wpdb->query("DROP TABLE IF EXISTS " . $prefix . "mx_seo_actions");
                $wpdb->query("DROP TABLE IF EXISTS " . $prefix . "mx_seo_urls");
                $wpdb->query("DROP TABLE IF EXISTS " . $prefix . "mx_seo_ignore");
                $wpdb->query("DROP TABLE IF EXISTS " . $prefix . "mx_seo_history");
            }
        }
    }

    /**
     * @since   1.0.0
     * @access  public
     * @param   string    $src
     * @return  void
     */
    public static function rrmdir($src, $limit=0) {

        if($limit == 0){
            $limit = time() + ini_get('max_execution_time');
        }
        if(time() > $limit - 5){
            return;
        }

        $dir = opendir($src);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if ( is_dir($full) ) {
                    self::rrmdir($full, $limit);
                }
                else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}