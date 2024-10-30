<?php
if (! defined('WPINC')) {
    die();
}

/**
 * This class is responsible for capturing Search Engine and visitors coming from search engines requests.
 *
 * @since 1.0.0
 * @package MatrixSeo
 * @subpackage MatrixSeo/includes
 * @author MatrixSeo <support@matrixseo.ai>
 */
class MatrixSeo_Reactor
{

    /**
     * MatrixSeo
     *
     * @since 1.0.0
     * @access private
     * @var MatrixSeo_Reactor
     */
    private static $instance;

    /**
     *
     * @since 1.0.0
     * @access private
     * @var MatrixSeo_Reactor
     */
    private static $newTitle = "";

    /**
     *
     * @since 1.0.5
     * @access private
     * @var bool
     */
    private static $react = true;

    /**
     * This function initializes the class and set its properties.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return MatrixSeo_Reactor
     */
    public static function getInstance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * This function initializes the class and set its properties.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     */
    public function __construct()
    {}

    /**
     * This function populates the urls table.
     *
     * @since 1.0.0
     * @access private
     * @param
     *            void
     * @return void
     */
    private static function populateSearchEngineTable()
    {
        MatrixSeo_Utils::cronDebug("Populating search engine table...", 3);
        global $wpdb;
        $filesData = self::getSearchEngineFilesAsArray(false);
        foreach ($filesData as $data) {
            $items = explode("\n", $data);
            foreach ($items as $item) {
                if ($item != '') {
                    $itemData = explode("\t", $item);
                    if (isset($itemData[1]) && filter_var($itemData[1], FILTER_VALIDATE_URL) !== false) {
                        $query = $wpdb->prepare("INSERT INTO " . $wpdb->prefix . "mx_seo_urls(url, url_plain, updates, update_ts)
														 VALUES('%s', '%s', '%d', '%s')
														 ON DUPLICATE KEY UPDATE
														 updates = updates + 1,
														 update_ts = '%s'
														 ", md5($itemData['1']), $itemData['1'], 1, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'));
                        $wpdb->query($query);
                    }
                }
            }
        }
        MatrixSeo_Utils::cronDebug("Search engine table populated.", 1);
    }

    /**
     * This function gets the Search Engine files and creates an array.
     *
     * @since 1.0.0
     * @access private
     * @param boolean $deleteAfterCall
     *            Determine if delete or not the files after they were sent to the API
     * @return array List of search engine files
     */
    private static function getSearchEngineFilesAsArray($deleteAfterCall = true)
    {
        $se_files = array();
        $theFiles = glob(MatrixSeo_Utils::getSearchEnginesDirectory('*.php'));
        foreach ($theFiles as $file) {
            if (basename($file) != "index.php") {
                $se_files[basename($file)] = MatrixSeo_Utils::getSafeFileContents($file);
                if ($deleteAfterCall) {
                    MatrixSeo_Utils::$deleteFilesQueue[] = $file;
                }
            }
        }
        MatrixSeo_Utils::cronDebug("Got search engine files as array.", 2);
        return $se_files;
    }

    /**
     * This function writes the HTML page source code to file when is visited by a search engine.
     *
     * @since 1.0.0
     * @access private
     * @param string $html
     * @return void
     */
    private static function setSearchEngineToFile($html = '')
    {
        $strippedPowerWords = implode(" ", self::getStrippedPowerWords($html));
        MatrixSeo_Utils::cronDebug("Stripped power words from html(" . strlen($html) . " characters): " . $strippedPowerWords . ".", 2);
        $data = array(
            "ip" => MatrixSeo_Utils::getVisitorIP(),
            "url" => MatrixSeo_Utils::getFullUrl(),
            "date" => MatrixSeo_Utils::getCurrentTime(),
            "title" => MatrixSeo_Utils::$currentTitle,
            "words" => $strippedPowerWords
        );
        
        $row = MatrixSeo_Utils::buildLogRow($data);
        
        MatrixSeo_Utils::writeLogRow("s_file", $row);
        
        MatrixSeo_Utils::cronDebug("Search engine visit set to file.", 1);
    }

    /**
     *
     * @since 1.0.9
     * @access private
     * @param string $html
     * @return string
     */
    private static function getCurrentTitle($html = '')
    {
        $res = preg_match("/<title>(.*)<\/title>/siU", $html, $title_matches);
        if (! $res)
            return '';
        $title = preg_replace('/\s+/', ' ', $title_matches[1]);
        $title = trim($title);
        return $title;
    }

    /**
     * This function is deleting marked files from local storage after OK received from API.
     *
     * @since 1.0.0
     * @access private
     * @param
     *            void
     * @return void
     */
    private static function processDeleteFilesQueue()
    {
        MatrixSeo_Utils::cronDebug("Deleting files marked for deletion...", 3);
        foreach (MatrixSeo_Utils::$deleteFilesQueue as $typeFiles) {
            foreach ($typeFiles as $fileForDeletion) {
                MatrixSeo_Utils::deleteFile($fileForDeletion);
            }
        }
        MatrixSeo_Utils::$deleteFilesQueue = array();
        MatrixSeo_Utils::cronDebug("Deleted files marked for deletion.", 2);
    }

    /**
     * This function gets page power words.
     *
     * @since 1.0.0
     * @access private
     * @param
     *            string %html Page html code.
     * @return array The words list.
     */
    private static function getStrippedPowerWords($html)
    {
        MatrixSeo_Utils::cronDebug("Removing history words from power words...", 3);
        $stripWords = self::getWordsHistory();
        $sourceWords = explode(" ", self::getPowerWordsTextVersion($html));
        $titleWords = explode(" ", MatrixSeo_Utils::$currentTitle);
        MatrixSeo_Utils::cronDebug("Removed history words from power words.", 2);
        return array_udiff($sourceWords, $stripWords, $titleWords, 'strcasecmp');
    }

    /**
     * This function is getting words history (words previously used as title for stripping power words purpose).
     *
     * @since 1.0.0
     * @access private
     * @param
     *            void
     * @return array The words list.
     */
    private static function getWordsHistory()
    {
        MatrixSeo_Utils::cronDebug("Getting words history...", 3);
        global $wpdb;
        $result_words = array();
        $hash = MatrixSeo_Utils::getFullUrl();
        $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mx_seo_history WHERE hash='%s'", md5($hash)), ARRAY_A);
        if (isset($result[0])) {
            $result_words = explode(' ', $result[0]['history']);
        }
        MatrixSeo_Utils::cronDebug("Got words history.", 2);
        return $result_words;
    }

    /**
     * This function gets the power words from HTML.
     *
     * @since 1.0.0
     * @access private
     * @param string $html
     * @return string
     */
    private static function getPowerWordsTextVersion($html)
    {
        MatrixSeo_Utils::cronDebug("Getting power words from HTML...", 3);
        $text = MatrixSeo_Utils::convertEncoding($html);
        $text = mb_strtolower($text);
        $text = preg_replace("/&(?:[a-z]+|#x?\d+);/iu", "", $text);
        $text = preg_replace('#<head.*?>([\s\S]*?)<\/head>#iu', '', $text);
        $text = preg_replace('#<script.*?>([\s\S]*?)<\/script>#iu', '', $text);
        $text = preg_replace('#<style.*?>([\s\S]*?)<\/style>#iu', '', $text);
        $text = preg_replace('#<a .*?>([\s\S]*?)<\/a>#iu', '', $text);
        
        $text = strip_tags(str_replace("<", " <", $text)); // strip tags keep space
        $text = preg_replace('/[\x00-\x1F\x7F\p{P}+]/u', ' ', $text); // remove special chars
        $text = MatrixSeo_Utils::singleSpacing($text);
        
        $text = MatrixSeo_Utils::removeStopWords($text);
        
        $words = explode(" ", $text);
        
        $wordsScore = array();
        foreach ($words as $key => $word) {
            if (strlen($word) > 3 && ! is_numeric($word)) {
                if (isset($wordsScore[$word])) {
                    $wordsScore[$word] ++;
                } else {
                    $wordsScore[$word] = 1;
                }
            }
        }
        arsort($wordsScore);
        $wordsScore = array_keys($wordsScore);
        $result = implode(" ", $wordsScore);
        MatrixSeo_Utils::cronDebug("Got power words from HTML.", 3);
        return $result;
    }

    /**
     * getRulesFromFile($url)
     * This function gets the rules from File and sets them into $this->rules[action]=data.
     *
     * @since 1.0.0
     * @access private
     * @param string $url
     *            The current URL
     * @return array The rules array
     */
    private static function getRulesFromFile($url)
    {
        MatrixSeo_Utils::cronDebug("Getting current URL rules from file...", 3);
        $md5 = md5($url);
        $rulesFile = MatrixSeo_Utils::getActionsDirectory(MatrixSeo_Utils::getPartialPath($md5));
        $rules = array();
        
        if (file_exists($rulesFile)) {
            $myRows = MatrixSeo_Utils::getSafeFileContents($rulesFile);
            $myRows = MatrixSeo_Utils::getArrayFromJSON($myRows);
            if ($myRows == false) {
                MatrixSeo_Utils::cronDebug("Can not get action rules from file.", 1);
                return $rules;
            }
            foreach ($myRows as $key => $row) {
                $rules[$key] = $row;
            }
        }
        MatrixSeo_Utils::cronDebug("Got URL action rules from file.", 2);
        return $rules;
    }

    /**
     * This funcion writes the site information to file when is visited by users with Search Engine Referer.
     *
     * @since 1.0.0
     * @access private
     * @param
     *            void
     * @return void
     */
    private static function setVisitorLogToFile()
    {
        MatrixSeo_Utils::cronDebug("Writing search engine referred visitor info to file...", 3);
        $data = array(
            "ip" => MatrixSeo_Utils::getVisitorIP(),
            "referrer" => MatrixSeo_Utils::getVisitorReferer(),
            "url" => MatrixSeo_Utils::getFullUrl(),
            "date" => MatrixSeo_Utils::getCurrentTime()
        );
        
        $row = MatrixSeo_Utils::buildLogRow($data);
        
        MatrixSeo_Utils::writeLogRow("r_file", $row);
        
        MatrixSeo_Utils::cronDebug("Wrote search engine referred visitor info to file.", 2);
    }

    /**
     * This function gets specified ignored URL rules number from database.
     *
     * @since 1.0.0
     * @access private
     * @param int $id
     * @return int
     */
    private static function getIgnoreRulesNumberForUrlFromDatabase($id)
    {
        if (is_numeric($id)) {
            MatrixSeo_Utils::cronDebug("Getting specified ignored URL rules number from database...", 3);
            global $wpdb;
            $results = $wpdb->get_row("SELECT count(*) AS `total` FROM " . $wpdb->prefix . "mx_seo_ignore WHERE `id_url` = " . $id, ARRAY_A);
            
            MatrixSeo_Utils::cronDebug("Got specified ignored URL rules number from database:" . $results['total'] . ".", 3);
            return $results['total'];
        }
        return 0;
    }

    /**
     * This function detects the visitor type.
     *
     * @since 1.0.0
     * @acces   public
     * @param
     *            void
     * @return void
     */
    public function detectAndSaveVisitor()
    {
        if (MatrixSeo_Config::get("mx_plugin_active") == "0" || MatrixSeo_Config::get("mx_plugin_active") == "2") {
            return;
        }
        
        $currentURL = MatrixSeo_Utils::getFullUrl();
        
        $skipUrlStrings = array(
            "/feed/",
            ".css",
            ".js",
            ".ico",
            ".htaccess",
            "wc-ajax="
        );
        
        foreach ($skipUrlStrings as $skipUrlString) {
            $check = strpos($currentURL, $skipUrlString);
            if ($check !== false) {
                self::$react = false;
            }
        }
        
        if (self::$react && ! is_admin() && ! defined('DOING_CRON')) {
            MatrixSeo_Utils::cronDebug("Detecting [ " . $currentURL . " ] visitor type...", 3);
            
            if ($this->detectSearchEngineByIP()) {
                MatrixSeo_Utils::cronDebug("Search engine detected on [ " . $currentURL . " ].", 1);
                ob_start(array(
                    'MatrixSeo_Reactor',
                    'setHtmlToFile'
                ));
            } 
            elseif ($this->detectReferredBySearchEngine()) {
                MatrixSeo_Utils::cronDebug("Visitor referred by search engine detected on [ " . $currentURL . " ].", 1);
                self::setVisitorLogToFile();
            }
        }
    }

    /**
     * This function detects visitor by Search Engine Referrer.
     *
     * @since 1.0.0
     * @access private
     * @param
     *            void
     * @return boolean
     */
    private function detectReferredBySearchEngine()
    {
        MatrixSeo_Utils::cronDebug("Detecting referred by search engine...", 3);
        $visitorReferer = MatrixSeo_Utils::getVisitorReferer();
        if ($visitorReferer == "") {
            MatrixSeo_Utils::cronDebug("Not referred by search engine.", 3);
            return false;
        }
        $seList = MatrixSeo_Utils::getReferrerMatchesFromFile();
        $visitorRefererHost = parse_url($visitorReferer, PHP_URL_HOST);
        foreach ($seList as $ref) {
            $ref = trim($ref);
            if ($ref != "" && preg_match($ref, $visitorRefererHost)) {
                MatrixSeo_Utils::cronDebug("Referred by search engine.", 2);
                return true;
            }
        }
        MatrixSeo_Utils::cronDebug("Not referred by search engine.", 3);
        return false;
    }

    public static function cronAddMatrixSeo($schedules)
    {
        if (! isset($schedules['mx_interval'])) {
            $schedules['mx_interval'] = array(
                'interval' => MatrixSeo_Config::getCallInterval(),
                'display' => __('MatrixSEO Interval')
            );
        }
        return $schedules;
    }

    /**
     * This function detects the Search Engine by IP.
     *
     * @since 1.0.0
     * @access private
     * @param
     *            void
     * @return boolean
     */
    private function detectSearchEngineByIP()
    {
        // TODO: cache the result
        $ips = MatrixSeo_Utils::getSearchEngineIPsFromFile();
        $visitorIP = trim(MatrixSeo_Utils::getVisitorIP());
        foreach ($ips as $ip) {
            if (MatrixSeo_Utils::isIpInRange($visitorIP, trim($ip))) {
                return true;
            }
        }
        return false;
    }

    /**
     * This function writes the actions to file.
     *
     * @since 1.0.0
     * @access public
     * @param string $md5
     *            Md5 of current URL
     * @param int $action
     *            Id of action
     * @param string $payload
     *            The payload for specified action
     * @return void
     */
    public function setDataToFile($md5, $action, $payload)
    {
        MatrixSeo_Utils::cronDebug("Writing actions to file...", 3);
        $myRows = array();
        $actionsFile = MatrixSeo_Utils::getActionsDirectory(MatrixSeo_Utils::getPartialPath($md5));
        if (file_exists($actionsFile)) {
            $tmpData = MatrixSeo_Utils::getSafeFileContents($actionsFile);
            $myRows = MatrixSeo_Utils::getArrayFromJSON($tmpData);
        }
        $myRows[$action] = $payload;
        $tmpData = MatrixSeo_Utils::getJSONFromArray($myRows);
        MatrixSeo_Utils::setSafeFileContents($actionsFile, $tmpData);
        MatrixSeo_Utils::cronDebug("Wrote actions to file.", 2);
    }

    /**
     * This function prepares the data received from API on the sync-data call.
     *
     * @since 1.0.0
     * @access public
     * @param array $readyData
     * @return void
     */
    public function prepareData($readyData = Array())
    {
        MatrixSeo_Utils::cronDebug("Preparing received data from API...", 3);
        global $wpdb;
        
        foreach ($readyData as $md5 => $data) {
            $resultWordsArr = array();
            $queryWords = "SELECT * FROM " . $wpdb->prefix . "mx_seo_history WHERE hash='" . $md5 . "';";
            $resultWords = $wpdb->get_results($queryWords, ARRAY_A);
            if (isset($resultWords[0])) {
                $resultWordsArr = explode(' ', $resultWords[0]['history']);
            }
            $words = array();
            
            /**
             * Statistics update ACT
             */
            $countData = count($data);
            if ($countData > 0) {
                $configTotalAct = (int) MatrixSeo_Config::get('mx_total_act');
                MatrixSeo_Config::set('mx_total_act', $configTotalAct + $countData);
                MatrixSeo_Utils::cronDebug("Total actions [ " . $configTotalAct . " ] incremented by [ " . $countData . " ]", 2);
            }
            // --
            
            foreach ($data as $key => $value) { // each action for current url (one URL may have multiple actions)
                if (MatrixSeo_Config::get("mx_save_page_content") == "0" && $key == 2) { // enable the content send
                    MatrixSeo_Config::set("mx_save_page_content", "1");
                }
                $this->setActionInDB($md5, $key, $value);
                $this->setDataToFile($md5, $key, $value);
                $newWords = explode(' ', $value);
                if ($key == 1) {
                    foreach ($newWords as $w) {
                        if ((! in_array($w, $resultWordsArr)) && (strlen($w) > 3)) {
                            array_push($words, $w);
                        }
                    }
                }
            }
            if (count($words) > 0) {
                $historyWords = implode(' ', $words);
                $query = $wpdb->prepare("INSERT INTO " . $wpdb->prefix . "mx_seo_history(hash, history)
									VALUES('%s', '%s')
									ON DUPLICATE KEY UPDATE history = CONCAT(history , ' ', '%s')", $md5, trim($historyWords), trim($historyWords));
                $wpdb->query($query);
            }
        }
    }

    /**
     * This function records current action to DataBase.
     *
     * @since 1.0.0
     * @access private
     * @param string $md5
     *            Md5 of current URL
     * @param int $action
     *            The action ID
     * @param string $payload
     *            The payload for specified action
     * @return void
     */
    private function setActionInDB($md5, $action, $payload)
    {
        if ($payload != "") {
            MatrixSeo_Utils::cronDebug("Writing action to DB...", 3);
            global $wpdb;
            $query = $wpdb->prepare("INSERT
    			INTO " . $wpdb->prefix . "mx_seo_actions( `hash`,`action_id`,`data` )
    			VALUES( '%s', %d,'%s' )
    			ON DUPLICATE KEY UPDATE data='%s'", $md5, $action, $payload, $payload);
            $wpdb->query($query);
            MatrixSeo_Utils::cronDebug("Wrote action to DB.", 2);
        } else {
            MatrixSeo_Utils::cronDebug("Empty action skipped from writing to database.", 2);
        }
    }

    /**
     * This function gets the HTML data from ob_start and saves it to file.
     *
     * @since 1.0.0
     * @acces   public
     * @param string $html
     *            HTML code of the page.
     * @return string HTML code of the page after saved.
     */
    public function setHtmlToFile($html = '')
    {
        $currentURL = MatrixSeo_Utils::getFullUrl();
        if (! defined('DOING_CRON') && strpos($currentURL, 'wp-cron.php') === false) {
            MatrixSeo_Utils::cronDebug("Getting HTML content of page...", 3);
            $title = MatrixSeo_Utils::$currentTitle;
            if(is_array($title)){
                $title = implode(' ', $title);
            }
            $data = array(
                'title' => $title,
                'content' => $html
            );
            if (MatrixSeo_Config::get("mx_save_page_content") == "1") {
                MatrixSeo_Utils::setPageContent($currentURL, MatrixSeo_Utils::getVisitorIP(), $data);
            }
            self::setSearchEngineToFile($html);
            MatrixSeo_Utils::cronDebug("Got HTML content of page.", 2);
            return $html;
        }
        return '';
    }

    /**
     * This function is sending the data collected to the API.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return void
     */
    public function send_data()
    {
        MatrixSeo_Utils::cronDebug("Cron activated at " . date("Y-m-d H:i:s"), 1);
        
        self::populateSearchEngineTable();
        $api = MatrixSeo_Api::getInstance();
        MatrixSeo_Utils::$deleteFilesQueue = array();
        
        list ($data, MatrixSeo_Utils::$deleteFilesQueue) = MatrixSeo_Utils::buildSyncDataPackage(MatrixSeo_Utils::getMemoryLimit());
        
        $response = $api->call('sync-data', array(), $data);
        
        if ((is_array($response)) && ($response['ok'] == true)) {
            
            if (isset($respose['upgrade'])) {
                if ($response['upgrade'] == true) {
                    MatrixSeo_Config::set("mx_need_upgrade", "1");
                } else {
                    MatrixSeo_Config::set("mx_need_upgrade", "2");
                }
            }
            
            if (isset($response['actions']) && ! empty($response['actions'])) {
                $this->prepareData($response['actions']);
            }
            
            self::processDeleteFilesQueue();
            
            /**
             * Statistics update S & R
             */
            $countSFiles = 0;
            foreach ($data['s_files'] as $sFile) {
                $rows = explode("\n", $sFile);
                $countSFiles += count($rows);
            }
            
            $countRFiles = 0;
            foreach ($data['r_files'] as $rFile) {
                $rows = explode("\n", $rFile);
                $countRFiles += count($rows);
            }
            
            if ($countSFiles > 0) {
                $configTotalS = (int) MatrixSeo_Config::get('mx_total_se');
                MatrixSeo_Config::set('mx_total_se', $configTotalS + $countSFiles);
                MatrixSeo_Utils::cronDebug("Total search engine visits [ " . $configTotalS . " ] incremented by [ " . $countSFiles . " ]", 2);
            }
            if ($countRFiles > 0) {
                $configTotalR = (int) MatrixSeo_Config::get('mx_total_ref');
                MatrixSeo_Config::set('mx_total_ref', $configTotalR + $countRFiles);
                MatrixSeo_Utils::cronDebug("Total search engine referred visitors incremented by [ " . $countRFiles . " ]", 2);
            }
            // --
            
            MatrixSeo_Utils::cronDebug("Collected data sent to API", 2);
        }
    }

    /**
     * This function gets the stop words from API.
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return bool
     */
    public static function getAPIStopWordsList()
    {
        MatrixSeo_Utils::cronDebug("Getting stop-words from API...", 3);
        $api = MatrixSeo_Api::getInstance();
        $response = $api->call('refresh-stop-words', array(
            'checksum' => MatrixSeo_Utils::getStopWordsCheckSum()
        ));
        if ((is_array($response)) && (isset($response['ok'])) && ($response['ok'] == true) && isset($response['checksum_validated'])) {
            if ($response['checksum_validated'] == false) {
                $decodedData = base64_decode($response['words_list']);
                MatrixSeo_Utils::setSafeFileContents(MatrixSeo_Utils::getStorageDirectory("stop-words.php"), $decodedData);
                MatrixSeo_Config::set('mx_stop_words_checksum', $response['checksum']);
                MatrixSeo_Utils::cronDebug("Updated stop words from MatrixSeo API.", 1);
            } else {
                MatrixSeo_Utils::cronDebug("Skipped updating stop words from MatrixSeo API.", 2);
            }
            return true;
        }
        return false;
    }

    /**
     *
     * @since 1.0.0
     * @access public
     * @param string $content
     * @return string mixed
     */
    public static function replaceTitle($content)
    {
        global $wpdb;
        $currentURL = MatrixSeo_Utils::getFullUrl();
        if (! is_admin() && ! defined('DOING_CRON') && self::$react) {
            $mxTitle = '';
            $rules = self::getRulesFromFile($currentURL);
            $urls = $wpdb->get_row("SELECT `id` FROM " . $wpdb->prefix . "mx_seo_urls WHERE `url_plain`='" . $currentURL . "'", ARRAY_A);
            $ignore = self::getIgnoreRulesNumberForUrlFromDatabase($urls['id']);
            if (isset($ignore) && isset($rules[1]) && $rules[1] != '' && $ignore == 0) {
                $mxTitle = stripslashes(MatrixSeo_Config::get('mx_beginning_title_separator')) . $rules[1] . stripslashes(MatrixSeo_Config::get('mx_end_title_separator'));
            }
            
            MatrixSeo_Utils::$currentTitle = self::getCurrentTitle($content);
            
            $content = preg_replace('/<title([^>]*?)\s*>([^<]*?)<\/title\s*>/is', 
            '<title$1>$2' . $mxTitle . '</title>', 
            $content);
        }
        return $content;
    }

    /**
     *
     * @since 1.0.8
     * @access public
     * @param string $content
     * @return string
     */
    public static function addSignature($content)
    {
        if (MatrixSeo_Config::get("mx_signature_active") === "1") {
            $content = preg_replace('/' . preg_quote('</title>', '/') . '/', "</title>\n<!-- Website enhanced by https://www.MatrixSEO.ai/ -->", $content, 1);
        }
        return $content;
    }

    /**
     *
     * @since 1.0.0
     * @access public
     * @param string $content
     * @return string
     */
    public static function outputCallback($content)
    {
        $content = self::replaceTitle($content);
        $content = self::addSignature($content);
        return $content;
    }

    /**
     *
     * @since 1.0.0
     * @access public
     * @param
     *            void
     * @return void
     */
    public static function template_redirect()
    {
        ob_start(array(
            'MatrixSeo_Reactor',
            'outputCallback'
        ));
    }
}

