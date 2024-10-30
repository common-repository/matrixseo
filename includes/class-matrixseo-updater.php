<?php
/**
 * Updates MatrixSeo env to the latest downloaded version of the plugin.
 *
 * @package    MatrixSeo
 * @subpackage MatrixSeo/includes
 * @author     MatrixSeo <support@matrixseo.ai>
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class MatrixSeo_Updater{

	/**
	 * @since   1.0.1
	 * @var     string  $version
	 */
	protected static $version;

	/**
	 * MatrixSeo_Updater constructor.
	 * @since   1.0.1
	 * @access  public
	 * @param   string  $version
	 */
	public function __construct($version){
		self::$version=$version;
	}

	/**
	 * @since   1.0.1
	 * @access  public
	 * @param   string  $newVersion
	 * @return  bool
	 */
	public static function changeVersion($newVersion){
		MatrixSeo_Config::set("mx_version",$newVersion);
		MatrixSeo_Utils::cronDebug("Updated ".MatrixSeo_Utils::MATRIXSEO." to ".$newVersion);
		return self::check();
	}
	/**
	 * @since   1.0.1
	 * @access  public
	 * @param   void
	 * @return  bool
	 */
	public static function check(){
		$currentVersion=MatrixSeo_Config::get("mx_version");

		if($currentVersion === false){
			$currentVersion="1.0.0";
		}

		if(version_compare($currentVersion,self::$version,'==')){
			return true;
		}

		if(version_compare($currentVersion,"1.0.1","lt")){
			MatrixSeo_Config::add("mx_version","1.0.1");
			MatrixSeo_Config::add("mx_signature_active","1");
			return self::changeVersion("1.0.1");
		}

		if(version_compare($currentVersion,"1.0.5","lt")){
			require_once dirname(__FILE__).DIRECTORY_SEPARATOR."class-matrixseo-activator.php";
			MatrixSeo_Activator::writeDefaultFiles();

			MatrixSeo_Config::add("mx_max_send_size","16000000");
			MatrixSeo_Config::add("mx_max_filesize","500000");
            MatrixSeo_Config::add("mx_interval", "3600");
            MatrixSeo_Config::add("mx_throttle_active","1");

			$s = array_diff(glob(MatrixSeo_Utils::getSearchEnginesDirectory('*.php')),array(MatrixSeo_Utils::getSearchEnginesDirectory("index.php")));
    		$r = array_diff(glob(MatrixSeo_Utils::getReferrersDirectory('*.php')),array(MatrixSeo_Utils::getReferrersDirectory("index.php")));
    		foreach($s as $item){
    			$newName=dirname($item).DIRECTORY_SEPARATOR."s_file_".MatrixSeo_Utils::generateRandomString(8,true,false).".php";
    			rename($item,$newName);
		    }
		    foreach ($r as $item){
			    $newName=dirname($item).DIRECTORY_SEPARATOR."r_file_".MatrixSeo_Utils::generateRandomString(8,true,false).".php";
			    rename($item,$newName);
		    }


			return self::changeVersion("1.0.5");
		}

		if(version_compare($currentVersion,self::$version,"lt")){
			return self::changeVersion(self::$version);
		}

		return false;
	}
}