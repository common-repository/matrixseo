<?php
if (! defined('WPINC')) {
    die();
}

class MatrixSeo_Utils
{

    const MATRIXSEO = "matrixseo";

    const MATRIXSEO_API_VERSION = "1.0";

    /**
     *
     * @var string
     * @access public
     */
    public static $apiKey = "";

    /**
     *
     * @var array
     * @access public
     */
    public static $currentTitle = "";

    /**
     *
     * @var array
     * @access public
     */
    public static $deleteFilesQueue = array();

    /**
     *
     * @var array
     * @access public
     */
    public static $deleteSendActionsFromDBQueue = array();

    /**
     *
     * @var string
     * @access public
     */
    public static $storageHeader = "\xEF\xBB\xBF<?php header(\$_SERVER[\"SERVER_PROTOCOL\"].\" 404 Not Found\");echo\"<h1>404 Not Found</h1>The page that you have requested could not be found.\";exit();?>";

    /**
     * This function gets the current page url including home.
     *
     * @since 1.0.0
     * @access public
     * @param boolean $showRequest
     *            if false show home url
     * @return string the url
     */
    public static function getFullUrl($showRequest = true)
    {
        $protocol = "http://";
        
        $homeUrl = parse_url(home_url());
        $fullHomeUrl = $homeUrl['scheme'] . '://' . $homeUrl['host'];
        
        $currentURL = $fullHomeUrl . add_query_arg(NULL, NULL);
        
        $url = parse_url($currentURL);
        
        if (isset($url['scheme']) && ! empty($url['scheme'])) {
            $protocol = $url['scheme'] . '://';
        }
        
        $host = str_replace(array(
            'http://',
            'https://'
        ), '', get_site_url());
        
        if (isset($url['host']) && ! empty($url['host'])) {
            $host = $url['host'];
        }
        
        $returnQuery = "";
        if ($showRequest) {
            if (isset($url['path']) && ! empty($url['path'])) {
                $returnQuery .= $url['path'];
            }
            
            if (isset($url['query'])) {
                parse_str($url['query'], $getPartsArr);
                $filterParams = self::getFilterParamsFromFile();
                
                foreach ($getPartsArr as $keyPart => $getPart) {
                    if (in_array($keyPart, $filterParams)) {
                        unset($getPartsArr[$keyPart]);
                    } else {
                        $getPartsArr[$keyPart] = $getPart;
                    }
                }
                
                if (count($getPartsArr) > 0) {
                    $getPartsReturn = http_build_query($getPartsArr);
                    $returnQuery .= "?" . $getPartsReturn;
                }
            }
        }
        
        $url = $protocol . $host . $returnQuery;
        
        return $url;
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            void
     * @return array
     */
    public static function getFilterParamsFromFile()
    {
        $stringArray = array();
        $stringWords = self::getSafeFileContents(self::getStorageDirectory("filter-params.php"));
        if (strlen($stringWords) > 1) {
            $stringArray = explode("\n", $stringWords);
            foreach ($stringArray as $key => $stringWord) {
                $stringArray[$key] = trim(strtolower($stringWord));
            }
        }
        return $stringArray;
    }

    /**
     * Decides if | and writes to the debug file.
     *
     * @since 1.0.0
     * @access public
     * @param string $what
     *            the data to write in the debug log
     * @param int $level
     *            level of debug 1 < 2 < 3
     * @param string $filename
     *            -> since 1.0.5
     * @return void
     */
    public static function cronDebug($what, $level = 1, $filename = "debug.php")
    {
        if (MatrixSeo_Config::get('mx_activate_cronlog') == '1' || MatrixSeo_Config::get('mx_activate_cronlog') === false) {
            if (MatrixSeo_Config::get('mx_debug_level') >= $level) {
                self::setSafeFileContents(self::getStorageDirectory($filename), date("Y-m-d H:i:s") . ": " . self::getDebugBacktrace() . $what . "\n", true);
            }
        }
    }

    /**
     * Gets the stop words checksum to check if something changed in the meanwhile.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return string
     */
    public static function getStopWordsCheckSum()
    {
        $checkSum = file_exists(self::getStorageDirectory('stop-words.php')) ? MatrixSeo_Config::get('mx_stop_words_checksum') : '000000';
        return $checkSum;
    }

    /**
     * Gets the IPs of Search Engines from the file.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return array list of ips from file separated by \n
     */
    public static function getSearchEngineIPsFromFile()
    {
        $ipList = "";
        $fileName = self::getStorageDirectory('seips.php');
        if (file_exists($fileName)) {
            $ipList = self::getSafeFileContents($fileName);
        }
        self::cronDebug("Got search engines IPs from file", 3);
        return explode("\n", $ipList);
    }

    /**
     * Gets the Search Engines Referrers that matches from the file.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return array array of referar rules from file
     */
    public static function getReferrerMatchesFromFile()
    {
        $refList = "";
        $fileName = self::getStorageDirectory('refs.php');
        if (file_exists($fileName)) {
            $refList = self::getSafeFileContents($fileName);
        }
        self::cronDebug("Got referrer fingerprints from file", 3);
        return explode("\n", $refList);
    }

    /**
     * Gets the visitors IP.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return bool
     */
    public static function getVisitorIP()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * Gets the visitors referer.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return bool
     */
    public static function getVisitorReferer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }

    /**
     * Gets the current time.
     *
     * @since 1.0.0
     * @access public
     * @param string $format
     *            The format to return
     * @return bool
     */
    public static function getCurrentTime($format = 'Y-m-d H:i:s')
    {
        return $format == '' ? time() : date($format);
    }

    /**
     * This function checks if an IP is in a RANGE.
     *
     * @since 1.0.0
     * @access public
     * @param string $ip
     *            IP address
     * @param string $range
     *            IP range
     * @return bool
     */
    public static function isIpInRange($ip, $range)
    {
        if (filter_var($range, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if (self::ipv4to6($ip) == self::ipv4to6($range) || self::checkIpv6InRange($ip, $range)) {
                return true;
            } else {
                return false;
            }
        }
        if (strpos($range, '-')) {
            $rangeIps = explode('-', $range);
            $rangeIps = array_map("ip2long", $rangeIps);
            $ip = ip2long($ip);
            return ($ip >= $rangeIps[0] && $ip <= $rangeIps[1]);
        } else {
            if (strpos($range, '/') === false) {
                $range .= '/32';
            }
            // $range is in IP/CIDR format eg 127.0.0.1/24
            list ($range, $netmask) = explode('/', $range, 2);
            $range_decimal = ip2long($range);
            $ip_decimal = ip2long($ip);
            $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
            $netmask_decimal = ~ $wildcard_decimal;
            return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
        }
    }

    /**
     *
     * @since 1.0.0
     * @access public
     * @param
     *            $ip
     * @param
     *            $range
     * @return bool
     */
    public static function checkIpv6InRange($ip, $range)
    {
        $rangeIps = explode('-', $range);
        
        if (count($rangeIps) != 2) {
            return false;
        }
        
        $ip = self::ipv4to6($ip);
        $start = self::ipv4to6($rangeIps[0]);
        $end = self::ipv4to6($rangeIps[1]);
        
        return (strlen($ip) == strlen($start) && $ip >= $start && $ip <= $end);
    }

    /**
     *
     * @since 1.0.0
     * @access public
     * @param $ip mixed
     * @return mixed
     */
    public static function ipv4to6($ip = false)
    {
        if (! $ip) {
            return;
        }
        
        $ipAddressBlocks = explode('.', $ip);
        
        if (count($ipAddressBlocks) == 0) {
            return;
        }
        
        $ipv6 = '';
        $ipv6Pieces = 0;
        foreach ($ipAddressBlocks as $ipAddressBlock) {
            if ($ipv6Pieces % 4 == 0 && $ipv6Pieces > 0) {
                $ipv6 .= '::';
            }
            
            $ipv6Piece = dechex($ipAddressBlock);
            $ipv6 .= (is_numeric($ipv6Piece) && $ipv6Piece < 10 ? '0' . $ipv6Piece : $ipv6Piece);
            
            $ipv6Pieces = strlen(str_replace('::', '', $ipv6));
        }
        
        return $ipv6;
    }

    /**
     * This function saves the page content to md5 file.
     *
     * @since 1.0.0
     * @access public
     * @param string $url
     *            Page url
     * @param string $ip
     *            The ip that visited the page
     * @param array $extra
     *            Data to be written to file
     * @return void
     */
    public static function setPageContent($url, $ip, $extra)
    {
        $data = array();
        $md5 = md5($url);
        foreach ($extra as $key => $value) {
            if (! isset($data[$key])) {
                $data[$key] = $value;
            }
        }
        $writeThis = MatrixSeo_Utils::getJSONFromArray($data);
        self::setSafeFileContents(self::getUrlsDirectory(self::getPartialPath($md5)), $writeThis);
        self::cronDebug("Page content saved", 1);
    }

    /**
     * This function generates the /a/b/c/abcde partial path from the abcde.
     *
     * @since 1.0.0
     * @access public
     * @param string $full
     *            The full path of the file
     * @return string Breadcrumbed path to the file
     */
    public static function getPartialPath($full)
    {
        $levelNo = 3; // Maybe we want to change it later...
        if (strlen($full) < $levelNo) {
            return $full;
        }
        $pathCrumbs = array();
        for ($i = 0; $i < $levelNo; $i ++) {
            $pathCrumbs[$i] = $full[$i];
        }
        $pathCrumbs[$levelNo] = $full;
        return implode(DIRECTORY_SEPARATOR, $pathCrumbs);
    }

    /**
     * This function gets the file content skipping the first line.
     *
     * @since 1.0.0
     * @access public
     *        
     * @param string $filePath
     *            The path to the file.
     *            
     * @return string
     */
    public static function getSafeFileContents($filePath)
    {
        if (! file_exists($filePath)) {
            return "";
        }
        $content = file_get_contents($filePath);
        $content = self::convertEncoding($content);
        
        $contentCrumbs = explode("\n", $content, 2);
        if ($contentCrumbs[0] == self::$storageHeader) {
            if (! isset($contentCrumbs[1])) {
                return false;
            }
            return trim($contentCrumbs[1]);
        }
        return trim($content);
    }

    /**
     * This function sets the file content after the first line.
     *
     * @since 1.0.0
     * @access public
     * @param string $dir
     *            Path to the file
     * @param string $contents
     *            Content to be written to the file
     * @param boolean $append
     *            True - Append, False - override
     * @return void
     */
    public static function setSafeFileContents($dir, $contents, $append = false)
    {
        $safeString = self::$storageHeader . "\n";
        $dir = str_replace(array(
            "/",
            "\\"
        ), DIRECTORY_SEPARATOR, $dir);
        
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $contents = str_replace("\r\n", "\n", $contents);
        }
        
        if (! file_exists($dir)) {
            $contents = $safeString . $contents;
            if (! is_dir(dirname($dir))) {
                mkdir(dirname($dir), 0775, true);
            }
        } else {
            if (! $append) {
                $contents = $safeString . $contents;
            }
        }
        file_put_contents($dir, $contents, $append ? FILE_APPEND : 0);
    }

    /**
     * This function validates an IP address.
     *
     * @since 1.0.0
     * @access public
     * @param string $ip
     * @return boolean
     */
    public static function validateIp($ip)
    {
        return $ip != '' && ! filter_var($ip, FILTER_VALIDATE_IP) === false;
    }

    /**
     * This function gets the path relatively to base.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return string
     */
    public static function getBasePath()
    {
        $basePath = array();
        $basePath[0] = dirname(plugin_dir_path(__FILE__));
        
        $argList = func_get_args();
        
        return str_replace(array(
            '/',
            '\\'
        ), DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, array_merge($basePath, $argList)));
    }

    /**
     * This function gets the path relatively to storage base.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return string
     */
    public static function getBaseStoragePath()
    {
        global $wpdb;
        $basePath = array();
        $basePath[] = WP_CONTENT_DIR;
        
        $basePath[] = MatrixSeo_Utils::MATRIXSEO;
        
        // add the MU parameter
        if ($wpdb->prefix != $wpdb->base_prefix) {
            $basePath[] = "mu";
            $basePath[] = $wpdb->blogid;
        }
        
        $argList = func_get_args();
        
        return str_replace(array(
            '/',
            '\\'
        ), DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, array_merge($basePath, $argList)));
    }

    /**
     * This function removes the stop words from given text.
     *
     * @since 1.0.0
     * @access public
     * @param string $text
     * @return string
     */
    public static function removeStopWords($text)
    {
        $textWords = explode(" ", $text);
        $file = self::getStorageDirectory("stop-words.php");
        $content = self::getSafeFileContents($file);
        $stopWords = explode("\n", $content);
        self::cronDebug("Loaded " . count($stopWords) . " stop-words.", 1);
        return implode(" ", array_udiff($textWords, $stopWords, 'strcasecmp'));
    }

    /**
     * This function replaces the double spacing with the single spacing.
     *
     * @since 1.0.0
     * @access public
     * @param string $string
     * @return string
     */
    public static function singleSpacing($string)
    {
        $newString = str_replace("  ", " ", $string);
        return $newString != $string ? self::singleSpacing($newString) : $newString;
    }

    /**
     * This function decodes an JSON item.
     *
     * @since 1.0.0
     * @access public
     * @param string $raw
     * @return array
     */
    public static function getArrayFromJSON($raw)
    {
        $data = json_decode($raw, true);
        return $data;
    }

    /**
     * This function encodes the array as JSON.
     *
     * @since 1.0.0
     * @access public
     * @param array $array
     * @return string
     */
    public static function getJSONFromArray($array)
    {
        $data = json_encode($array);
        return $data;
    }

    /**
     * This function gets the path to the storage directory.
     *
     * @since 1.0.0
     * @access public
     * @param string $item
     * @return string
     */
    public static function getStorageDirectory($item = '')
    {
        return self::getBaseStoragePath($item);
    }

    /**
     * This function gets the path to the actions directory.
     *
     * @since 1.0.0
     * @access public
     * @param string $item
     * @return string
     */
    public static function getActionsDirectory($item = '')
    {
        return self::getBaseStoragePath('actions', $item);
    }

    /**
     * This function gets the path to the search engines directory.
     *
     * @since 1.0.0
     * @access public
     * @param string $item
     * @return string
     */
    public static function getSearchEnginesDirectory($item = '')
    {
        return self::getBaseStoragePath('s_files', $item);
    }

    /**
     * This function gets the path to the visitors directory.
     *
     * @since 1.0.0
     * @access public
     * @param string $item
     * @return string
     */
    public static function getReferrersDirectory($item = '')
    {
        return self::getBaseStoragePath('r_files', $item);
    }

    /**
     * This function gets the path to the urls directory.
     *
     * @since 1.0.0
     * @access public
     * @param string $item
     * @return string
     */
    public static function getUrlsDirectory($item = '')
    {
        return self::getBaseStoragePath('urls', $item);
    }

    /**
     * This function converts the string encoding (UTF-8 Default).
     *
     * @since 1.0.0
     * @access public
     * @param string $string
     *            The string to convert
     * @param string $target_encoding
     *            The target encoding type
     * @return string Encoded text
     */
    public static function convertEncoding($string, $target_encoding = 'UTF-8')
    {
        $encoding = mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true);
        $target = str_replace("?", "[question_mark]", $string);
        $target = mb_convert_encoding($target, $target_encoding, $encoding);
        $target = str_replace("?", "", $target);
        $target = str_replace("[question_mark]", "?", $target);
        return $target;
    }

    /**
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return string
     */
    public static function getDebugBacktrace()
    {
        $file = '';
        $func = '';
        $class = '';
        $trace = debug_backtrace();
        if (isset($trace[2])) {
            $file = $trace[1]['file'];
            $func = $trace[2]['function'];
            if ((substr($func, 0, 7) == 'include') || (substr($func, 0, 7) == 'require')) {
                $func = '';
            }
        } else if (isset($trace[1])) {
            $file = $trace[1]['file'];
            $func = '';
        }
        if (isset($trace[3]['class'])) {
            $class = $trace[3]['class'];
            $func = $trace[3]['function'];
            $file = $trace[2]['file'];
        } else if (isset($trace[2]['class'])) {
            $class = $trace[2]['class'];
            $func = $trace[2]['function'];
            $file = $trace[1]['file'];
        }
        if ($file != '')
            $file = basename($file);
        $c = '';
        if (MatrixSeo_Config::get('mx_debug_level') == 2) {
            $c .= $file . ": ";
        } elseif (MatrixSeo_Config::get('mx_debug_level') == 3) {
            $c .= $file . ": ";
            $c .= ($class != '') ? $class . "->" : "";
            $c .= ($func != '') ? $func . "(): " : "";
        }
        return ($c);
    }

    /**
     * This function deletes the files.
     *
     * @since 1.0.0
     * @access public
     * @param string $file
     * @return void
     */
    public static function deleteFile($file)
    {
        if (file_exists($file)) {
            unlink($file);
            MatrixSeo_Utils::cronDebug("Deleted " . $file . ". ", 2);
        }
    }

    /**
     * This function deletes the action files
     *
     * @param
     *            $dir
     * @return bool
     */
    public static function deleteActionsFiles($dir = false)
    {
        if (! $dir) {
            $dir = MatrixSeo_Utils::getActionsDirectory();
        }
        $files = array_diff(scandir($dir), array(
            '.',
            '..',
            'index.php'
        ));
        
        foreach ($files as $file) {
            if (is_dir("$dir" . DIRECTORY_SEPARATOR . "$file")) {
                self::deleteActionsFiles("$dir" . DIRECTORY_SEPARATOR . "$file");
            } else {
                unlink("$dir" . DIRECTORY_SEPARATOR . "$file");
            }
        }
        if ($dir != MatrixSeo_Utils::getActionsDirectory()) {
            return rmdir($dir);
        }
        return false;
    }

    /**
     * GZinflate data
     *
     * @since 1.0.0
     * @access public
     * @param string $data
     * @return string
     */
    public static function gzDecode($data)
    {
        return @gzinflate(base64_decode($data));
    }

    /**
     *
     * @since 1.0.0
     * @access public
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    public static function humanFilesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return str_replace(".00", "", sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor]);
    }

    /**
     *
     * @since 1.0.0
     *        #access public
     * @param string $url
     * @return string
     */
    public static function cleanURL($url)
    {
        $parts = explode("/", $url);
        foreach ($parts as $key => $part) {
            if ($part == "..") {
                if ($parts[$key - 1]) {
                    unset($parts[$key - 1]);
                    unset($parts[$key]);
                }
            }
        }
        $url = implode("/", $parts);
        return esc_url($url);
    }

    /**
     *
     * @since 1.0.3
     * @access public
     * @param
     *            void
     * @return string
     */
    public static function debugTail()
    {
        $readSize = 1024 * 10;
        $debugFile = MatrixSeo_Utils::getStorageDirectory('debug.php');
        $fp = fopen($debugFile, 'r');
        $fs = filesize($debugFile);
        fseek($fp, $fs - $readSize > 0 ? $fs - $readSize : 0);
        $fc = fread($fp, $readSize);
        fclose($fp);
        $fcp = explode("\n", $fc);
        unset($fcp[0]);
        $fcp = array_reverse($fcp);
        $debugContent = (implode("\n", $fcp));
        return trim($debugContent);
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param string $type
     * @param string $log_row
     * @return void
     */
    public static function writeLogRow($type, $log_row)
    {
        $full_path = self::getStorageDirectory("{$type}s" . DIRECTORY_SEPARATOR . "{$type}.php");
        self::appendLogRow($full_path, $log_row, MatrixSeo_Config::get("mx_max_filesize"));
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param $full_path, $log_row,
     *            $max_file_size
     * @return void
     */
    public static function appendLogRow($full_path, $log_row, $max_file_size)
    {
        clearstatcache(true, $full_path);
        $csize = (file_exists($full_path) ? filesize($full_path) : 0);
        if (($csize + strlen($log_row)) > $max_file_size) {
            $new_full_path = self::generateSuffixedFile($full_path);
            rename($full_path, $new_full_path);
        }
        self::setSafeFileContents($full_path, $log_row . "\n", true);
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param string $full_path
     * @return string
     */
    public static function generateSuffixedFile($full_path)
    {
        $pathinfo = pathinfo($full_path);
        $filename = str_replace('.' . $pathinfo['extension'], '', $pathinfo['basename']);
        $filename .= '_' . self::generateRandomString(8, true);
        return $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $filename . "." . $pathinfo['extension'];
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param $len, $readable,
     *            $hash
     * @return string
     */
    public static function generateRandomString($len, $readable = false, $hash = false)
    {
        $string = '';
        if ($hash) {
            $string = substr(sha1(uniqid(rand(), true)), 0, $len);
        } elseif ($readable) {
            $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
            for ($i = 0; $i < $len; ++ $i) {
                $string .= strtolower(substr($chars, (mt_rand() % strlen($chars)), 1));
            }
        } else {
            for ($i = 0; $i < $len; ++ $i) {
                $string .= chr(mt_rand(33, 126));
            }
        }
        return $string;
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            $row_data
     * @return mixed
     */
    public static function buildLogRow($row_data)
    {
        $result = false;
        if (! empty($row_data)) {
            $sanitized_data = array();
            foreach ($row_data as $field => $value) {
                if ($field == 'ip')
                    $value = self::sanitizeIp($value);
                elseif ($field == 'date')
                    $value = self::sanitizeDate($value);
                elseif ($field == 'url')
                    $value = self::sanitizeUrl($value);
                elseif ($field == 'referrer')
                    $value = self::sanitizeReferrer($value);
                elseif ($field == 'title')
                    $value = self::sanitizeTitle($value);
                elseif ($field == 'words')
                    $value = self::sanitizeWords($value);
                
                $sanitized_data[$field] = $value;
            }
            $result = implode("\t", array_values($sanitized_data));
        }
        return $result;
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            $ip
     * @return string
     */
    public static function sanitizeIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            $date
     * @return string
     */
    public static function sanitizeDate($date)
    {
        $date = date_parse($date);
        if (! $date['error_count']) {
            return $date['year'] . '-' . $date['month'] . '-' . $date['day'] . ' ' . $date['hour'] . ':' . $date['minute'] . ':' . $date['second'];
        }
        return false;
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            $url
     * @return string
     */
    public static function sanitizeUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            $referrer_url
     * @return string
     */
    public static function sanitizeReferrer($referrer_url)
    {
        return filter_var($referrer_url, FILTER_VALIDATE_URL);
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            $title
     * @return string
     */
    public static function sanitizeTitle($title)
    {
        $title = self::singleSpacing(str_replace("\t", " ", $title));
        return filter_var($title, FILTER_SANITIZE_STRING);
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            $words
     * @return string
     */
    public static function sanitizeWords($words)
    {
        $words = self::singleSpacing(str_replace("\t", " ", $words));
        return filter_var($words, FILTER_SANITIZE_STRING);
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            $max_data_size
     * @return array
     */
    public static function buildSyncDataPackage($max_data_size = 8000000)
    {
        $throttleCall = "empty";
        $max_data_size = floor($max_data_size / 2);
        $maxConfigSize = MatrixSeo_Config::get("mx_max_send_size");
        if ($max_data_size > $maxConfigSize) {
            $max_data_size = $maxConfigSize;
        }
        $total_package_size = 0;
        $flag = false;
        $package_files = array();
        $delete_files_queue = array();
        $all_files = array(
            's_files' => array_diff(glob(self::getSearchEnginesDirectory('*.php')), array(
                MatrixSeo_Utils::getSearchEnginesDirectory("index.php")
            )),
            'r_files' => array_diff(glob(self::getReferrersDirectory('*.php')), array(
                MatrixSeo_Utils::getReferrersDirectory("index.php")
            )),
            'contents' => array()
        );
        $data_types = array_keys($all_files);
        foreach ($data_types as $ctype) {
            $package_files[$ctype] = array();
        }
        // build data package
        while (! $flag) {
            $flag = true;
            foreach ($data_types as $ctype) {
                $current_file = array_shift($all_files[$ctype]);
                if (! empty($current_file)) {
                    clearstatcache(true, $current_file);
                    $csize = filesize($current_file);
                    if (($total_package_size + $csize) < $max_data_size) {
                        $package_files[$ctype][basename($current_file)] = self::getSafeFileContents($current_file);
                        $delete_files_queue[$ctype][] = $current_file;
                        $total_package_size += $csize;
                        $flag = false;
                    }
                }
            }
        }
        // set throttle value
        foreach ($all_files as $all_file) {
            if (count($all_file) > 0) {
                $throttleCall = "full";
            }
        }
        if ($throttleCall == "empty") {
            foreach ($package_files as $package_file) {
                if (count($package_file) > 0) {
                    $throttleCall = "normal";
                    break;
                }
            }
        }
        MatrixSeo_Config::updateThrottle($throttleCall);
        return array(
            $package_files,
            $delete_files_queue
        );
    }

    /**
     *
     * @since 1.0.5
     * @access public
     * @param
     *            void
     * @return int
     */
    public static function getMemoryLimit()
    {
        $memory_limit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
            if ($matches[2] == 'G') {
                $memory_limit = $matches[1] * 1024 * 1024 * 1024;
            } else if ($matches[2] == 'M') {
                $memory_limit = $matches[1] * 1024 * 1024;
            } else if ($matches[2] == 'K') {
                $memory_limit = $matches[1] * 1024;
            }
        }
        return $memory_limit;
    }
	
    /**
     *
     * @since 1.0.10
     * @access public
     * @param
     *            void
     * @return int
     */
    public static function setTimezone()
    {
        $timezone = '';
        $timezone = get_option('timezone_string');
        
        if($timezone){
            date_default_timezone_set($timezone);
        }
        
    }
}
