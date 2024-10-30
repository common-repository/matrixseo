<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.matrixseo.ai
 * @since      1.0.0
 *
 * @package    MatrixSeo
 * @subpackage MatrixSeo/admin
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class MatrixSeo_Admin {
	
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $MatrixSeo    The ID of this plugin.
	 */
	private $MatrixSeo;
	
	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */

	private $version;
	/**
	 * This function initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 * @acces public
	 * @param    string    $MatrixSeo       The name of this plugin.
	 * @param    string    $version   		The version of this plugin.
	 */
	public function __construct( $MatrixSeo, $version ) {
		$this->MatrixSeo = $MatrixSeo;
		$this->version = $version;
	}
	
	/**
	 * This function creates the Settings link for the plugin.
	 *
	 * @since 1.0.0
	 * @acces public
	 * @param 	array $links
     * @param   string  $file
	 * @return 	array $links
	 */
	public function matrixseo_action_links($links, $file){
		if($file == 'matrixseo/matrixseo.php'){
			$links[] = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=matrixseo&tab=stats') ) .'">Settings</a>';
		}
		return $links;
	}
	
	/**
	 * This function generates the tabs for the plugin settings page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param 		string $current 	Current settings tab
	 * @return 		string	The tabs to be displayed
	 */
	public function matrixseo_tabs($current = 'stats'){
		$tabs = array(
			'stats' 	=> __('Stats', MatrixSeo_Utils::MATRIXSEO),
			'settings' 	=> __('Settings', MatrixSeo_Utils::MATRIXSEO),
			'actions' 	=> __('Actions', MatrixSeo_Utils::MATRIXSEO),
			'advanced' 	=> __('Advanced', MatrixSeo_Utils::MATRIXSEO),
			'debug'     => __('Debug', MatrixSeo_Utils::MATRIXSEO)
	    );

		$html =  '<h2 class="nav-tab-wrapper">';
		foreach( $tabs as $tab => $name ){
		    $style = "";
			$class = ($tab == $current) ? 'nav-tab-active' : '';
			if($tab == 'debug'){
			    $style.=' id="debug-tab" ';
			    if(MatrixSeo_Config::get('mx_activate_cronlog') == '0') {
				    $style .= ' style="display:none;" ';
			    }
            }
			$html .=  '<a class="nav-tab '.$class.'" '.$style.' href="?page=matrixseo&tab='.$tab.'">' . $name . '</a>';
		}
		$html .= '</h2>';
		return $html;
	}
	
	/**
	 * This function adds the plugin settings page to the menu.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param 	void
	 * @return 	void
	 */
	public function matrixseo_add_menu_page(){
		add_options_page('MatrixSEO Settings', 'MatrixSEO', 'manage_options', 'matrixseo', array($this, 'matrixseo_page'));
	}

	public function matrixseo_activate_fallback(){
        if(MatrixSeo_Config::get('mx_key') === false || !file_exists(MatrixSeo_Utils::getStorageDirectory('seips.php'))){
            require_once MatrixSeo_Utils::getBasePath('includes'.DIRECTORY_SEPARATOR.'class-matrixseo-activator.php');
            MatrixSeo_Activator::activate();
        }
    }

	/**
	 * This function represents the plugin settings page, and displays the view for the settings page and processes settings.
	 * 
	 * @since 1.0.0
	 * @access public
     * @param   void
	 * @return 	void
	 */

	public function matrixseo_page(){
		global $wpdb;

		$term="";
		$api		= MatrixSeo_Api::getInstance();
		$reactor	= MatrixSeo_Reactor::getInstance();

		if(isset($_GET['force']) && $_GET['force']==MatrixSeo_Config::getKey()){
		    $reactor->send_data();
        }

		if(MatrixSeo_Config::get('mx_need_upgrade')==1){
		    self::displayError("You are generating too much traffic to MatrixSeo API Servers. You must upgrade your license to premium. <b><a href=\"https://matrixseo.ai/?op=upgrade&key=".MatrixSeo_Config::get("mx_key")."\" target=\"_blank\">MatrixSEO PRO</a></b>", 'need-premium');
        }

		if($_SERVER['REQUEST_METHOD'] === 'POST'){
			
			if( isset( $_POST['ips'] ) && isset($_POST['allow_edit_ips']) && $_POST['allow_edit_ips']=="on" ){
			    $ips = sanitize_textarea_field($_POST['ips']);
				self::setSEIPsToFile($ips);
				self::displaySuccess("IPs modified.");
				MatrixSeo_Utils::cronDebug("IPs modified", 1);
			}
			if( isset( $_POST['referers'] ) && isset($_POST['allow_edit_refs']) && $_POST['allow_edit_refs']=="on" ){
                $referers = sanitize_textarea_field($_POST['referers']);
				self::setRefsToFile($referers);
				self::displaySuccess("Referrer fingerprints modified.");
				MatrixSeo_Utils::cronDebug("Referrer fingerprints modified", 1);
			} 
		}
		
		$tab = (!empty($_GET['tab']))? esc_attr($_GET['tab']) : 'stats';
		$tabs = $this->matrixseo_tabs($tab);
		
		if($tab === 'settings'){
			$ips		= MatrixSeo_Utils::getSearchEngineIPsFromFile();
			$referers	= MatrixSeo_Utils::getReferrerMatchesFromFile();
		}

        if($tab == 'actions'){
            $results = $wpdb->get_results("SELECT
                  urls.id as urlsid,
                  urls.url,
                  urls.url_plain,
                  actions.hash,
                  actions.action_id,
                  actions.data,
                  (SELECT COUNT(id_url) FROM ".$wpdb->prefix."mx_seo_ignore ig WHERE ig.id_url = urlsid) as total
                  FROM ".$wpdb->prefix."mx_seo_urls urls
                  JOIN ".$wpdb->prefix."mx_seo_actions actions
                  ON urls.url = actions.hash
                  HAVING total = 0 LIMIT 10" ,ARRAY_A);

            $ignored_data = $wpdb->get_results(
                                        "SELECT 
                                                      urls.id as igid, 
                                                      urls.url_plain as igdata, 
                                                      actions.action_id as action_id, 
                                                      actions.data as actiondata 
                                                FROM ".$wpdb->prefix."mx_seo_ignore ignr 
												JOIN ".$wpdb->prefix."mx_seo_urls urls 
												    ON urls.id = ignr.id_url
												JOIN ".$wpdb->prefix."mx_seo_actions actions 
												    ON actions.hash = urls.url
												WHERE actions.action_id <> 2 LIMIT 10;
												");
        }

        if(isset($_GET['inactivenotice']) && is_numeric($_GET['inactivenotice']) && $_GET['inactivenotice'] == 1){
            MatrixSeo_Config::set('mx_plugin_active', 2);
        }

		if($tab == 'debug'){
			$cronContent = MatrixSeo_Utils::debugTail();
		}
		
		if(isset($_GET['searchurl'])){
			if( isset($_GET['searchurl']) && !empty($_GET['searchurl']) ){
				$term = sanitize_text_field($_GET['searchurl']);
				$term = str_replace(array('*'), '', $term);
				$type = filter_var($term, FILTER_VALIDATE_URL) === false ? 'REGEXP' : 'LIKE';
				
				if($type == 'LIKE'){
					$term = '%'.$term.'%';
				}
				
				$search_result = $wpdb->get_results($wpdb->prepare("
                    (SELECT 
                        urls.id as id_website, 
                        actions.action_id, 
                        actions.data, 
                        urls.*,
                        (SELECT COUNT(id_url) FROM ".$wpdb->prefix."mx_seo_ignore ig WHERE ig.id_url = id_website) as noIgnored
                    FROM ".$wpdb->prefix."mx_seo_urls as urls
                    LEFT JOIN ".$wpdb->prefix."mx_seo_actions as actions ON urls.url = actions.hash
                    WHERE actions.action_id <> 2
                    AND url_plain $type '%s'
                    HAVING noIgnored = 0
                    LIMIT 10)
                    UNION 
                    (SELECT 
                        urls.id as id_website, 
                        actions.action_id, 
                        actions.data, 
                        urls.*,
                        (SELECT COUNT(id_url) FROM ".$wpdb->prefix."mx_seo_ignore ig WHERE ig.id_url = id_website) as noIgnored
                    FROM ".$wpdb->prefix."mx_seo_urls as urls
                    LEFT JOIN ".$wpdb->prefix."mx_seo_actions as actions ON urls.url = actions.hash
                    WHERE actions.action_id <> 2
                    AND url_plain $type '%s'
                    HAVING noIgnored <> 0
                    LIMIT 10)
                    ", $term, $term), ARRAY_A);
			}
		}
		
		if($tab == 'advanced' && $_SERVER['REQUEST_METHOD'] === 'POST'){
		    if(isset($_GET['separators'])) {
                $begining_title_separator = sanitize_text_field($_POST['mx_beginning_title_separator']);
                MatrixSeo_Config::set('mx_beginning_title_separator', $begining_title_separator);
                $end_title_separator = sanitize_text_field($_POST['mx_end_title_separator']);
                MatrixSeo_Config::set('mx_end_title_separator', $end_title_separator);
                self::displaySuccess("Separators saved");
            }


		}

		include MatrixSeo_Utils::getBasePath('admin','views','matrixseo-admin-views.php');
	}
	
	
	/**
	 * This function generates the new cron intervals.
	 *
     * @since   1.0.0
     * @access  public
	 * @param 		array 	$schedules 		Wordpress crons array.
	 * @return 		array
	 */
	public function matrixseo_add_schedules( $schedules ) {
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display' => __('Every week', MatrixSeo_Utils::MATRIXSEO)
        );
        return $schedules;
	}
	

	/**
	 * This function generates the new cron intervals.
	 *
     * @since   1.0.0
     * @access  public
	 * @param 		void
	 * @return 		void
	 */
    public function matrixseo_add_dashboard_widgets() {
        wp_add_dashboard_widget(
        	'mx_dashboard_widget',
            'Matrix SEO',
            'MatrixSeo_Admin::dashboard_widget_function'
        );
    }

    /**
     * @since   1.0.0
     * @access  public
     * @param   void
     * @return  void
     */
    public function matrixseo_disabled_notice()
    {
        require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'matrixseo-admin-notice.php';
    }

    /**
     * This function generates the new cron intervals
     *
     * @since   1.0.0
     * @access  public
     * @param 		void
     * @return 		void
     */
    public static function dashboard_widget_function() {
        include dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'matrixseo-admin-widget.php';
    }
    
    /**
     * This function generates the Wordpress error.
     *
     * @since   1.0.0
     * @access  public
     * @param string    $error
     * @return  void
     */
    
    public static function displayError($error, $class = ''){
    	$return = '<div class="msnotice msnotice-error notice notice-error '.$class.'">';
        $return .= '<img src="'.plugins_url("img/error.png",__FILE__).'">&nbsp;';
    	$return .= "<span>".__($error, MatrixSeo_Utils::MATRIXSEO)."</span>";
    	$return .= '</div>';
    	MatrixSeo_Utils::cronDebug("Error displayed: ".$error, 1);
    	echo $return;
    }
    
    /**
     * This function generates the Wordpress success message.
     * @since   1.0.0
     * @access  public
     * @param string    $message
     * @return  void
     */
    public static function displaySuccess($message){
    	$return = '<div class="msnotice msnotice-success notice notice-success">';
    	$return .= '<img src="'.plugins_url("img/success.png",__FILE__).'">&nbsp;';
    	$return .= "<span>".__($message, MatrixSeo_Utils::MATRIXSEO, 1)."</span>";
    	$return .= '</div>';
    	
    	echo $return;
    }
    
    /**
     * This function generates the Wordpress notice.
     *
     * @since   1.0.0
     * @access  public
     * @param string    $message
     * @return  void
     */
    public static function displayNotice($message){
    	$return = '<div class="msnotice msnotice-warning notice notice-warning">';
        $return .= '<img src="'.plugins_url("img/warning.png",__FILE__).'">&nbsp;';
    	$return .= "<span>".__($message, MatrixSeo_Utils::MATRIXSEO, 1)."</span>";
    	$return .= '</div>';
    	
    	echo $return;
    }

    /**
     * @since   1.0.0
     * @access  public
     * @param 	string 	$ip
     * @param 	array 	$array
     * @return  bool
     */
    public static function isIpInArray($ip,$array){
        $found=false;
        if(is_array($array)){
            foreach($array as $tmp){
                if(MatrixSeo_Utils::isIpInRange($ip,$tmp)){
                    $found=true;
                    break;
                }
            }
        }
        return $found;
    }

    /**
     * This function writes the IPs of the Search Engines to file.
     *
     * @since   1.0.0
     * @access  public
     * @param 	array 	$data IPs that will be written to a file
     * @return  void
     */
    public static function setSEIPsToFile($data){
    	$write='';
    	$invalidDetected=false;
    	
    	$form_ips = explode( "\n", $data );
    	$form_ips = array_map( "trim",$form_ips );
    	$form_ips = array_unique( $form_ips );
    	$writeArray = array();
    	foreach( $form_ips as $ip ){

    		$netmask='';
    		$tmpValid=true;
    		
    		if(strpos( $ip,' - ' )){
    			$ipS=explode( ' - ', $ip,2 );
    			if( MatrixSeo_Utils::validateIp( $ipS[0] ) && MatrixSeo_Utils::validateIp( $ipS[1] ) ){
    				$write.=$ip."\n";
    				if(!self::isIpInArray($ip,$writeArray)){
    				    $writeArray[]=$ip;
                    }
    			}
    			else{
    				$invalidDetected=true;
    			}
    		}
    		elseif( strpos( $ip,'-' ) ){
    			$ipS=explode( '-', $ip,2 );
    			if( MatrixSeo_Utils::validateIp( $ipS[0] ) && MatrixSeo_Utils::validateIp( $ipS[1] ) ){

                        $write.=$ip."\n";
                        if(!self::isIpInArray($ip,$writeArray)){
                            $writeArray[]=$ip;
                        }
    			}
    			else{
    				$invalidDetected=true;
    			}
    		}
    		else{
    			if( strpos( $ip,'/' ) ){
    				list( $ip, $netmask ) = explode( '/', $ip, 2 );
    				if( strpos( $netmask,'/' ) ){
    					$tmpValid=false;
    				}
    			}
    			if( 	$tmpValid &&
    					MatrixSeo_Utils::validateIp( $ip ) &&
    					( $netmask=='' || ( is_numeric( $netmask ) && $netmask<=32 && $netmask>=0 ) )
    					){
    						if( $netmask!='' ){
    							$ip.="/".$netmask;
    						}
    						$write.=$ip."\n";
                            if(!self::isIpInArray($ip,$writeArray)){
                                $writeArray[]=$ip;
                            }
    			}
    			elseif( $ip!='' ) { // dont show warning if its just an empty line
    				$invalidDetected=true;
    			}
    		}
    		//--

    	}
    	MatrixSeo_Utils::setSafeFileContents( MatrixSeo_Utils::getStorageDirectory('seips.php'), implode("\n",$writeArray) ); //substr to eliminate the last \n
    	if( $invalidDetected ){
    		MatrixSeo_Utils::cronDebug("IPs set to file, with invalid data filtered out.", 1);
    	}
    	else{
    		MatrixSeo_Utils::cronDebug("IPs set to file", 1);
    	}
    }
    
    
    /**
     * This function writes the Search Engines Referrers to the file.
     *
     * @since   1.0.0
     * @access  public
     * @param 	array   $data   Array of Referars that will be written to a file
     * @return  void
     */
    public static function setRefsToFile($data){
    	
    	$write='';
    	$invalidDetected=false;
    	$form_refs = explode("\n", $data);
    	$form_refs = array_map("trim",$form_refs);
    	$form_refs = array_unique($form_refs);
    	foreach($form_refs as $referrer){
    		if(		$referrer!= '' &&
    				!( @preg_match( $referrer, null ) === false ) )	// smart regex expression validation)
    		{
    			$write.=$referrer."\n";
    		}
    		elseif($referrer!= ''){// dont show warning if its just an empty line
    			$invalidDetected=true;
    		}
    	}
    	MatrixSeo_Utils::setSafeFileContents(MatrixSeo_Utils::getStorageDirectory('refs.php'), substr( $write,0,-1 ) ); //substr to eliminate the last \n
    	if( $invalidDetected ){
    		MatrixSeo_Utils::cronDebug("Referrer fingerprints saved to file, with invalid data filtered out.", 1);
    	}
    	else{
    		MatrixSeo_Utils::cronDebug("Search engines referrer fingerprints saved to file.", 1);
    	}
    }

	/**
	 * @since   1.0.3
	 * @access  public
	 * @param   void
	 * @return  void
	 */
    public static function matrixseo_ajax_actions(){
	    $knownActions=array(
            "debug_level",
            "clear_log",
            "change_signature",
            "ignore_action",
            "apply_action",
            "activate_debug",
            "activate_plugin",
            "debug_log",
            "repopulate-settings",
            "repopulate-actions",
            "update-stopwords",
            "delete_files",
        );

	    // Sanitize the action
	    if(!in_array($_POST['what'],$knownActions)){
	    	wp_die();
	    }

	    global $wpdb;

        switch($_POST['what']){

		    case "debug_level":
		    	    if(isset($_POST['level']) && in_array($_POST['level'],array("1","2","3"))) {
				        MatrixSeo_Config::set( 'mx_debug_level', (string)$_POST['level'] );
			        }
		    	break;

            case "clear_log":
		            MatrixSeo_Utils::setSafeFileContents(MatrixSeo_Utils::getStorageDirectory('debug.php'),"");
                break;

            case "change_signature":
                    if(isset($_POST['value']) && in_array($_POST['value'],array('0','1'))){
	                    MatrixSeo_Config::set("mx_signature_active", (string)$_POST['value']);
	                    MatrixSeo_Utils::cronDebug("Plugin signature [ ".$_POST['value']." ]",2);
                    }

                break;

            case "ignore_action":
                    if(isset($_POST['value']) && is_numeric($_POST['value'])){
	                    $urlId = (int)$_POST['value'];
	                    $item = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."mx_seo_urls WHERE id = '%s'", $urlId), ARRAY_A );
	                    $wpdb->query( $wpdb->prepare("INSERT INTO ".$wpdb->prefix."mx_seo_ignore(id_url) VALUES('%d')", $item['id']) );
	                    if(!is_null($item)){
		                    MatrixSeo_Utils::cronDebug("Item added to ignore [ ".$item['url_plain']." ]", 2);
	                    }
                    }
                break;

		    case "apply_action":
                    if(isset($_POST['value']) && is_numeric($_POST['value'])){
                        $urlId = (int)$_POST['value'];
	                    $item = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."mx_seo_urls WHERE id = '%s'", $urlId), ARRAY_A );
	                    $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix."mx_seo_ignore WHERE id_url = %s", $urlId) );
                        if(!is_null($item)) {
                            MatrixSeo_Utils::cronDebug("{$urlId} Item removed from ignore [ " . $item['url_plain'] . " ]", 2);
                        }
                    }
			    break;

		    case "activate_debug":
                    if(isset($_POST['value']) && in_array($_POST['value'],array('0','1'))){
                        MatrixSeo_Config::set('mx_activate_cronlog', (string)$_POST['value']);
                        MatrixSeo_Utils::cronDebug("Debug log [ ".$_POST['value']." ]", 1);
                    }
			    break;

            case "activate_plugin":
                    if(isset($_POST['value']) && in_array($_POST['value'],array('0','1'))){
                        $response=array();
                        $response['reload']=false;
                        MatrixSeo_Config::set("mx_plugin_active",(string)$_POST['value']);
                        if($_POST['value']=="1") {
                            if(MatrixSeo_Config::get('mx_key')===false){
                                $response['reload']=true;
                            }
                            require_once MatrixSeo_Utils::getBasePath("includes" . DIRECTORY_SEPARATOR . "class-matrixseo-activator.php");
                            MatrixSeo_Activator::activate();
                        };
                        MatrixSeo_Utils::cronDebug("Plugin status set to [ ".(string)$_POST['value']." ]");
                        echo json_encode($response);
                    }
                break;

            case "debug_log":
                    $response=array();
                    $response['debug']=MatrixSeo_Utils::debugTail();
                    $response['size']=MatrixSeo_Utils::humanFilesize(filesize(MatrixSeo_Utils::getStorageDirectory("debug.php")));
                    echo json_encode($response);
                break;

            case "repopulate-settings":
                    $response=array();
                    $api=MatrixSeo_API::getInstance();
                    MatrixSeo_Utils::cronDebug("User requested repopulate-settings.",1);
                    $apiResponse=$api->call('repopulate-settings' );
                    if(isset($apiResponse['seips'])){
                        $seIps = sanitize_textarea_field( implode("\n", $apiResponse['seips']) );
                        self::setSEIPsToFile($seIps);
                        MatrixSeo_Utils::cronDebug("SEIps repopulated", 2);
                        $response['ips'] = implode("\n",MatrixSeo_Utils::getSearchEngineIPsFromFile());
                    }
                    if(isset($apiResponse['refs'])){
                        $refs = sanitize_textarea_field(implode( "\n", $apiResponse['refs'] ));
                        self::setRefsToFile($refs);
                        MatrixSeo_Utils::cronDebug("Refs repopulated", 2);
	                    $response['referers'] = implode("\n",MatrixSeo_Utils::getReferrerMatchesFromFile());
                    }
                    echo json_encode($response);
                break;

            case "repopulate-actions":
                    $reactor=MatrixSeo_Reactor::getInstance();
		            $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."mx_seo_actions ",ARRAY_A );
		            foreach( $results as $result ){
			            $reactor->setDataToFile( $result['hash'], $result['action_id'], $result['data'] );
		            }
		            MatrixSeo_Utils::cronDebug("Actions repopulated", 2);
                break;

            case "update-stopwords":
		            $updatedStopwords = MatrixSeo_Reactor::getAPIStopWordsList();
		            if($updatedStopwords){
			            MatrixSeo_Utils::cronDebug("Stop words refreshed", 2);
		            }else{
			            MatrixSeo_Utils::cronDebug("Couldn't refresh stop words", 2);
		            }
                break;
            case "delete_files":
                $theSeFiles = glob(MatrixSeo_Utils::getSearchEnginesDirectory('*.php'));
                $theRefFiles = glob(MatrixSeo_Utils::getReferrersDirectory('*.php'));
                $theFiles = array_merge($theSeFiles, $theRefFiles);
                $safeFiles = array(MatrixSeo_Utils::getSearchEnginesDirectory('index.php'), MatrixSeo_Utils::getReferrersDirectory('index.php'));
                MatrixSeo_Utils::cronDebug("Deleting files marked for deletion...", 3);

                foreach ($theFiles as $file) {
                    if(!in_array($file, $safeFiles)) {
                        MatrixSeo_Utils::deleteFile($file);
                    }
                }
                MatrixSeo_Utils::deleteActionsFiles();
                MatrixSeo_Utils::cronDebug("Internal Files deleted", 1);
                break;

	    }

    	wp_die();
    }
	/**
	 * @since   1.0.3
	 * @access  public
	 * @param   void
	 * @return  void
	 */
	public static function matrixseo_add_js(){
	    if(isset($_GET['page']) && $_GET['page']==MatrixSeo_Utils::MATRIXSEO) {
		    ?>
<!-- <?php echo MatrixSeo_Utils::MATRIXSEO; ?> JS -->
<script type="text/javascript">
jQuery(document).ready(function ($) {

	<?php include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "matrixseo-admin-js.php"; ?>

});
</script>
<!-- /<?php echo MatrixSeo_Utils::MATRIXSEO; ?> JS -->
		    <?php
	    }
	}

	/**
	 * @since   1.0.4
     * @access  public
     * @param   void
     * @return  void
	 */
	public static function matrixseo_add_css(){
		if(isset($_GET['page']) && $_GET['page']==MatrixSeo_Utils::MATRIXSEO) {
			?>
            <!-- <?php echo MatrixSeo_Utils::MATRIXSEO; ?> CSS -->
            <style>

					<?php include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "matrixseo-admin-css.php"; ?>

            </style>
            <!-- /<?php echo MatrixSeo_Utils::MATRIXSEO; ?> CSS -->
			<?php
		}
    }
}