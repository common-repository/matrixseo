<?php
/**
 * The MatrixSeo core plugin class.
 *
 * @since      1.0.0
 * @package    MatrixSeo
 * @subpackage MatrixSeo/includes
 * @author     MatrixSeo <support@matrixseo.ai>
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

class MatrixSeo {
	/**
	 * @since    1.0.0
	 * @access   protected
	 * @var      MatrixSeo_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

    /**
     * The API.
     * @since    1.0.0
     * @access   protected
     * @var      MatrixSeo_API    $api
     */
    protected $api;

	/**
	 * The reactor.
	 * @since    1.0.0
	 * @access   protected
	 * @var      MatrixSeo_Reactor    $reactor
	 */
	protected $reactor;
	
	/**
	 * The unique identifier of this plugin.
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $MatrixSeo    The string used to uniquely identify this plugin.
	 */
	protected $MatrixSeo;
	
	/**
	 * The current version of the plugin.
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
	
	/**
	 * This function sets the plugin name and the plugin version that can be used throughout the plugin.
	 * Loads the dependencies, defines the locale, and sets the hooks for the admin area and
	 * the public-facing side of the site.
	 * @since   1.0.0
     * @access  public
	 * @param   void
	 */
	public function __construct() {
        $this->MatrixSeo = 'matrixseo';
		$this->version = '1.0.10';
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}
	
	/**
	 * This function retrieves the version number of the plugin.
	 * @since   1.0.0
     * @access  public
	 * @param   void
	 * @return  string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * This function loads the required dependencies for this plugin.
	 * Includes the following files that make up the plugin:
	 * - MatrixSeo_Loader. Orchestrates the hooks of the plugin.
	 * - MatrixSeo_Admin. Defines all of the hooks for the admin area.
	 * Creates an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 * @since   1.0.0
	 * @access  private
	 * @param   void
	 * @return  void
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes'.DIRECTORY_SEPARATOR.'class-matrixseo-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin'.DIRECTORY_SEPARATOR.'class-matrixseo-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib'.DIRECTORY_SEPARATOR.'class-matrixseo-reactor.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib'.DIRECTORY_SEPARATOR.'class-matrixseo-utils.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib'.DIRECTORY_SEPARATOR.'class-matrixseo-api.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib'.DIRECTORY_SEPARATOR.'class-matrixseo-config.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes'.DIRECTORY_SEPARATOR.'class-matrixseo-updater.php';

        $this->loader	= new MatrixSeo_Loader();
		$this->api		= MatrixSeo_Api::getInstance();
		$this->reactor  = MatrixSeo_Reactor::getInstance();
	}
	
	/**
	 * This function registers all of the hooks related to the admin area functionality
	 * of the plugin.
	 * @since   1.0.0
	 * @access  private
	 * @param   void
	 * @return  void
	 */
	private function define_admin_hooks() {
		$plugin_admin   = new MatrixSeo_Admin( $this->get_MatrixSeo(), $this->get_version() );
		$plugin_updater = new MatrixSeo_Updater( $this->get_version() );

        $this->loader->add_action( 'init',              $plugin_admin, 'matrixseo_activate_fallback');
        $this->loader->add_action( 'plugins_loaded',         $plugin_updater, 'check' );

        $this->loader->add_filter( 'plugin_action_links',		$plugin_admin, 'matrixseo_action_links', 10, 5);
        $this->loader->add_action( 'admin_menu',				$plugin_admin, 'matrixseo_add_menu_page' );
        $this->loader->add_action( 'admin_head',              $plugin_admin, 'matrixseo_add_css');
        $this->loader->add_action( 'admin_footer',            $plugin_admin, 'matrixseo_add_js');
		$this->loader->add_action( 'wp_ajax_matrixseo_ajax_actions', $plugin_admin, 'matrixseo_ajax_actions');

		$mxPluginActive=MatrixSeo_Config::get("mx_plugin_active");
        if($mxPluginActive=="1") {
            $this->loader->add_filter('cron_schedules', $plugin_admin, 'matrixseo_add_schedules');
        }
        elseif($mxPluginActive=="0"){
            $this->loader->add_action("admin_notices", $plugin_admin,'matrixseo_disabled_notice');
        }
        elseif($mxPluginActive=="2"){
            //nothing
        }

        /*
         * Widget enabler
         */
        if(MatrixSeo_Config::get("mx_widget_enabled")=="1") {
            $this->loader->add_action( 'wp_dashboard_setup',      $plugin_admin, 'matrixseo_add_dashboard_widgets' );
        }

	}
	
	/** 
	 * This function registers all of the hooks related to the public-facing functionality
	 * of the plugin.
	 * @since   1.0.0
	 * @access  private
	 * @param   void
	 * @return  void
	 */
	private function define_public_hooks() {
        if(MatrixSeo_Config::get("mx_plugin_active")=="0" || MatrixSeo_Config::get("mx_plugin_active")=="2"){
            return;
        }

        $this->loader->add_filter( 'cron_schedules',            $this->reactor, 'cronAddMatrixSeo', 99999 );
        $this->loader->add_action( 'wp_loaded',                 $this->reactor, 'detectAndSaveVisitor', 0 );
        $this->loader->add_action( 'template_redirect',         $this->reactor, 'template_redirect' , 0 );

        $this->loader->add_action( 'matrixseocronjob',			$this->reactor, 'send_data' );
        $this->loader->add_action( 'matrixseostopwords',          $this->reactor,'getAPIStopWordsList');
    }
	
	/**
	 * This function runs the loader to execute all of the hooks with WordPress.
	 * @since   1.0.0
     * @access  public
	 * @param   void
	 * @return  void
	 */
	public function run() {
		$this->loader->run();
	}
	
	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 * @since   1.0.0
     * @access  public
	 * @param   void
	 * @return  string      The name of the plugin.
	 */
	public function get_MatrixSeo() {
		return $this->MatrixSeo;
	}
	
	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 * @since   1.0.0
     * @access  publix
	 * @param   void
	 * @return  MatrixSeo_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}