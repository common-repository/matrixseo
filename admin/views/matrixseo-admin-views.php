<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.matrixseo.ai
 * @since      1.0.0
 *
 * @package    MatrixSeo
 * @subpackage MatrixSeo/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

?>

<div class="header_wrap">
    <div class="pull-left">
        <a href="https://www.matrixseo.ai/" target="_blank" style="float:left;">
            <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/logo_matrixseo.png",__FILE__)); ?>" alt="" title="MatrixSeo">
        </a>
        <?php
        $pluginStatus=MatrixSeo_Config::get("mx_plugin_active");
        ?>
        <div style="float:right; padding:40px 20px 0 40px ;">
            <label style="color:#2c9869; display:inline; font-weight:bold; letter-spacing: 1px;"><input type="radio" name="active" class="activator" value="1" <?php echo $pluginStatus=="1"?"checked":""; ?> />Active</label>
            <label style="color:#d02020; display:inline; font-weight:bold; letter-spacing: 1px;"><input type="radio" name="active" class="activator" value="0" <?php echo ($pluginStatus=="0" || $pluginStatus=="2")?"checked":""; ?> />Inactive</label>
        </div>
    </div>

    <div class="hamburger">
        <a href="" class="btn btn-hamburger">&#9776;</a>
    </div>
    <?php echo $tabs; ?>
</div>

<?php if($tab == 'stats'): ?>
    <ul class="stats-container">
        <li>
            <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/search_engine_visits.png",__FILE__)); ?>">
            <strong><?php _e('Search engine visits', MatrixSeo_Utils::MATRIXSEO); ?></strong>
            <span class="pull-right stats-content">
                    <?php echo MatrixSeo_Config::get('mx_total_se'); ?>
                </span>
        </li>
        <li>
            <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/visitors_search_engines.png",__FILE__));?>">
            <strong><?php _e('Visitors from search engines', MatrixSeo_Utils::MATRIXSEO); ?></strong>
            <span class="pull-right stats-content">
                    <?php echo MatrixSeo_Config::get('mx_total_ref'); ?>
                </span>
        </li>
        <li>
            <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/actions_30days.png",__FILE__));?>">
            <strong><?php _e('Total Actions Received (from API)', MatrixSeo_Utils::MATRIXSEO); ?></strong>
            <span class="pull-right stats-content">
                    <?php echo MatrixSeo_Config::get('mx_total_act'); ?>
                </span>
        </li>
    </ul>
<?php elseif ($tab == 'actions'): ?>
    <div class="matrixseo-actions">
        <br>
        <div class="bar-container">
                <span class="pull-left" style="display: inline-block;width:auto;">
                    <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/actions_30days.png",__FILE__));?>">
                    <?php if($term==''): ?>
                        <h2><?php _e('Actions',  MatrixSeo_Utils::MATRIXSEO); ?></h2>
                    <?php else: ?>
                        <h2>
                            <?php _e('Search ',  MatrixSeo_Utils::MATRIXSEO); ?>
                            "<?php echo esc_attr($term); ?>"
                        </h2>
                    <?php endif; ?>
                </span>`
            <div class="pull-right" style="display: inline-block;">
                <div class="search-actions-form">
                    <form action="<?php echo MatrixSeo_Utils::getFullUrl(); ?>" method="GET" id="sForm">
                        <div>
                            <input type="text" name="searchedterm" class="search-ignore" placeholder="Search URL" id="url-term" value="<?php echo esc_attr($term); ?>">
                        </div>
                        <div>
                            <input type="submit" value="Search" class="button button-default" id="search-url">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="clearfix" style="clear: both;"></div>
        <br>
        <?php
        $domain=MatrixSeo_Utils::getFullUrl(false);
        if( isset($results) && count($results) && !isset($search_result) ): ?>
            <small style="margin-top: 4px; display: inline-block;float: right;"><i><?php _e('Last 10 actions', MatrixSeo_Utils::MATRIXSEO); ?></i></small>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                <tr>
                    <th>URL</th>
                    <th>Action</th>
                    <th>Data</th>
                    <th>Ignore</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($results as $res): ?>
                    <tr>
                        <td><?php echo str_replace($domain,"",$res['url_plain']); ?></td>
                        <td><?php echo $res['action_id'] == 1 ? 'Enhance Title' : 'Change Content'; ?></td>
                        <td><?php echo esc_attr($res['data']); ?></td>
                        <td>
                            <a class="action_item" href="#" data-id="<?php echo $res['urlsid']; ?>">Ignore</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif(isset($search_result) && count($search_result)): ?>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                <tr>
                    <th>URL</th>
                    <th>Action</th>
                    <th>Data</th>
                    <th>Ignore</th>
                </tr>
                </thead>
                <tbody>
                <?php $found=0; ?>
                <?php foreach($search_result as $res): ?>
                    <?php if($res['noIgnored']==0): ?>
                        <?php $found+=1; ?>
                    <tr>
                        <td><?php echo str_replace($domain,"",$res['url_plain']); ?></td>
                        <td><?php echo $res['action_id'] == 1 ? 'Enhance Title' : 'Change Content'; ?></td>
                        <td><?php echo esc_attr($res['data']); ?></td>
                        <td>
                            <a class="action_item" href="#" data-id="<?php echo $res['id_website']; ?>">Ignore</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if($found==0): ?>
                <tr><td colspan="4">No actions matching your search</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
<br /><br />
            <h2>Ignored URLs containing "<?php echo esc_attr($term); ?>"</h2>
            <small style="margin-top: 4px; display: inline-block;float: right;"><i><?php _e('Last 10 ignored actions', MatrixSeo_Utils::MATRIXSEO); ?></i></small>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                <tr>
                    <th>URL</th>
                    <th>Action</th>
                    <th>Data</th>
                    <th>Apply</th>
                </tr>
                </thead>
                <tbody>
                <?php $found=0; ?>
                <?php foreach($search_result as $res): ?>
                    <?php if($res['noIgnored']!=0): ?>
                        <?php $found+=1; ?>
                    <tr>
                        <td>
                            <?php echo str_replace($domain,"",$res['url_plain']); ?>
                        </td>
                        <td>
                            <?php echo $res['action_id'] == 1 ? 'Enhance Title' : 'Change Content'; ?>
                        </td>
                        <td>
                            <?php echo esc_attr($res['data']); ?>
                        </td>
                        <td>
                            <a class="remove_ig_item" href="#" data-id="<?php echo $res['id_website']; ?>">Apply</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if($found==0): ?>
                    <tr><td colspan="4">No ignored actions matching your search</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <h2>
                <a href="options-general.php?page=matrixseo&tab=actions&searchurloptions-general_php?page=matrixseo" class="button button-primary">Cancel Search ?</a>
            </h2>
        <?php else: ?>
            <?php if(isset($search_result) && !count($search_result)): ?>
                <em><?php $this->displayNotice('There are no results for the searched term'); ?></em>
                <em><?php _e('There are no actions for the searched term'); ?></em>
            <?php else: ?>
                <em><?php $this->displayNotice('There are no actions.'); ?></em>
                <p><?php _e('You haven\'t received any actions from the MatrixSeo API yet.'); ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <br>
        <?php if(isset($ignored_data) && !empty($ignored_data) && !isset($search_result)): ?>
            <h2>Ignored</h2>
            <small style="margin-top: 4px; display: inline-block;float: right;"><i><?php _e('Last 10 ignored actions', MatrixSeo_Utils::MATRIXSEO); ?></i></small>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                <tr>
                    <th>URL</th>
                    <th>Action</th>
                    <th>Data</th>
                    <th>Apply</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($ignored_data as $res): ?>
                    <tr>
                        <td>
                            <?php echo str_replace($domain,"",$res->igdata); ?>
                        </td>
                        <td>
                            <?php echo $res->action_id == 1 ? 'Enhance Title' : 'Change Content'; ?>
                        </td>
                        <td>
                            <?php echo esc_attr($res->actiondata); ?>
                        </td>
                        <td>
                            <a class="remove_ig_item" href="#" data-id="<?php echo $res->igid; ?>">Apply</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php elseif ($tab == 'settings'): ?>
    <div class="matrixseo-settings">
        <form method="post" action="<?php echo MatrixSeo_Utils::cleanURL(admin_url("options-general.php?page=matrixseo&tab=settings")); ?>" class="settings-form">

            <div class="half left-half">
                <div class="inside">
                    <div class="form-group apiKey">
                        <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/api_key.png",__FILE__));?>" style="margin-left:-2px;margin-right: 15px;margin-top: -7px;">
                        <h2><?php _e('API Key',MatrixSeo_Utils::MATRIXSEO); ?></h2><br>
                        <input type="text" class="apiKeyValue" readonly="readonly" value="<?php echo MatrixSeo_Config::getKey() == false ? __('There is no key',MatrixSeo_Utils::MATRIXSEO) : MatrixSeo_Config::getKey(); ?>">
                        <div class="element-info">
                            <?php
                            $needUpgrade=MatrixSeo_Config::get("mx_need_upgrade");
                            if($needUpgrade=="0" || $needUpgrade=="1") {
                                _e('Upgrade to <b><a href="https://www.matrixseo.ai/?op=upgrade&key=');
                                echo MatrixSeo_Config::get("mx_key");
                                _e('" target="_blank">MatrixSEO PRO</a></b>.');
                            }
                            elseif($needUpgrade=="2"){
                                _e("You are using <b><a href=\"https://www.matrixseo.ai/\" target=\"_blank\">MatrixSEO PRO</a></b>.");
                            }
                            ?>
                        </div>
                    </div><br>
                </div>
            </div>
            <div class="half right-half" id="debugLevel">
                <div class="inside">
                    <div class="debug-title-cont" style="margin-top: -5px;">
                        <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/debug.png",__FILE__));?>">
                        <h2>Debug</h2>
                        <div class="pull-right-debug" style="width: 60%;">
                            <input type="checkbox" name="mx_activate_cronlog" id="activateLogs"
                                   <?php if(MatrixSeo_Config::get('mx_activate_cronlog') == '1'){ ?>checked="checked" <?php } ?>
                            >
                            <label for="mx_activate_cronlog">Activate debug</label>
                        </div>
                    </div>
                    <table class="debug-table">
                        <td style="width: 200px;">
                            <p style="float:left; clear:left;margin-bottom: 5px;"><?php _e("Debug Level", MatrixSeo_Utils::MATRIXSEO); ?></p>
                            <select name="debug_level" id="debug_level" style="float: right;margin-top: 8px;margin-left: 10px;">
                                <option value="1" <?php echo MatrixSeo_Config::get('mx_debug_level') == "1" ? 'selected="selected"' : '' ?>>Basic</option>
                                <option value="2" <?php echo MatrixSeo_Config::get('mx_debug_level') == "2" ? 'selected="selected"' : '' ?>>Medium</option>
                                <option value="3" <?php echo MatrixSeo_Config::get('mx_debug_level') == "3" ? 'selected="selected"' : '' ?>>Max Verbose</option>
                            </select>
                        </td>
                        <td id="debugLvl" style="width:calc(100% - 200px); padding-top:6px;">
                            <?php if(MatrixSeo_Config::get('mx_debug_level') == "3"){ ?>
                                <div class="ms-alert ms-alert-danger" id="lvlMax">
                                    <?php _e('(Current debug file size: <b>'.MatrixSeo_Utils::humanFilesize(filesize(MatrixSeo_Utils::getStorageDirectory("debug.php"))).'</b>)', MatrixSeo_Utils::MATRIXSEO); ?>
                                </div>
                            <?php } ?>
                        </td>
                        </tr>
                    </table>
                    <div class="element-info">
                        <?php _e('Observe MatrixSeo decisions and traffic.', MatrixSeo_Utils::MATRIXSEO); ?><br>
                    </div>
                    <span style="clear: both;"></span>
                </div>
            </div>
            <div style="clear:both;"></div>
            <div class="full-width">
                <div class="full-inside">
                    <div class="left-half">
                        <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/search_engine_visits.png",__FILE__));?>">
                        <h2><?php _e('Search Engines', MatrixSeo_Utils::MATRIXSEO); ?></h2>
                        <?php settings_fields( 'matrixseo_settings' ); ?>
                        <?php do_settings_sections( 'matrixseo_settings' ); ?>
                        <div class="form-group">
                            <label for="ips"><?php _e('Search Engines IPs', MatrixSeo_Utils::MATRIXSEO); ?></label>
                            <textarea id="ips" class="form-control" cols="30" rows="8" name="ips" disabled="disabled"><?php
                                if(!empty($ips)){
                                    foreach($ips as $ip){
                                        if($ip != ''){
                                            echo $ip."\n";
                                        }
                                    }
                                }
                                ?></textarea>
                            <div class="enable-ips-box">
                                <input type="checkbox" name="allow_edit_ips" id="allow_edit_ips"> <?php _e('Edit IPs', MatrixSeo_Utils::MATRIXSEO); ?>
                            </div>
                        </div>
                    </div>
                    <div class="right-half">
                        <div class="form-group">
                            <label for="referers"><?php _e('Search Engines Referrer Fingerprints', MatrixSeo_Utils::MATRIXSEO); ?></label>
                            <textarea id="referers" class="form-control" cols="30" rows="8" name="referers" disabled="disabled"><?php
                                if(!empty($referers)){
                                    foreach($referers as $referer){
                                        if($referer != ''){
                                            echo $referer."\n";
                                        }
                                    }
                                }
                                ?></textarea>
                            <div class="enable-refs-box">
                                <input type="checkbox" name="allow_edit_refs" id="allow_edit_refs"> <?php _e('Edit Fingerprints', MatrixSeo_Utils::MATRIXSEO); ?>
                            </div>
                        </div>
                    </div>
                    <br style="clear:both;" />
                    <div class="form-buttons">
                        <a href="#" id="repopulate-settings" class="button button-success"><?php _e('Repopulate Settings To Default Values', MatrixSeo_Utils::MATRIXSEO); ?></a>
                        <?php submit_button(); ?>
                    </div>
                    <div class="element-info">
                        <?php _e('IPs used to identify search engines and search engines referrer fingerprints.') ?>
                    </div>
                    <div style="clear:both;"></div>
                </div>
            </div>
        </form>
    </div>
<?php elseif ($tab == 'debug'): ?>
    <div class="matrixseo-debug">



        <div class="bar-container">
            <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/debug.png",__FILE__));?>">
            <h2><?php _e('Debug Tail');?></h2>
            <div class="pull-right-debug">
                <small style="margin-top: 4px; display: inline-block;"><i>
                        <span><?php _e('Updates every <b>10</b> seconds.', MatrixSeo_Utils::MATRIXSEO); ?></span>
                        <span><?php _e('Current filesize:', MatrixSeo_Utils::MATRIXSEO); ?> <b id="debug-size"><?php echo MatrixSeo_Utils::humanFilesize(filesize(MatrixSeo_Utils::getStorageDirectory("debug.php")));?></b> </span>
                    </i></small>
                <span><a href="<?php echo MatrixSeo_Utils::cleanURL(admin_url('options-general.php?page=matrixseo&tab=debug&clearlog=1')); ?>" class="button button-default" id="clearLog"><?php _e('Clear Log', MatrixSeo_Utils::MATRIXSEO); ?></a></span>
            </div>
        </div>
        <div class="cron-log-display">
            <textarea style="width: 100%;" id="cronContainer" spellcheck="false"><?php echo $cronContent; ?></textarea>
        </div>
    </div>
<?php elseif ($tab == 'advanced'): ?>
        <div class="third">
            <div class="inside">
                <div class="title-sep-container titles-box">
                    <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/title_separators.png",__FILE__));?>">
                    <h2><?php _e('Title Separators', MatrixSeo_Utils::MATRIXSEO); ?></h2>
                </div>
                <form method="post" action="<?php echo MatrixSeo_Utils::cleanURL(admin_url( "options-general.php?page=matrixseo&tab=advanced&separators=1" )); ?>">
                    <div class="form-group">
                        <label for="mx_beginning_title_separator"><?php _e('Start Title Separator', MatrixSeo_Utils::MATRIXSEO); ?></label>
                        <input name="mx_beginning_title_separator" type="text" value="<?php echo stripslashes(MatrixSeo_Config::get('mx_beginning_title_separator')); ?>">
                    </div>
                    <br />
                    <div class="form-group">
                        <label for="mx_end_title_separator"><?php _e('End Title Separator', MatrixSeo_Utils::MATRIXSEO); ?></label>
                        <input name="mx_end_title_separator" type="text" value="<?php echo stripslashes(MatrixSeo_Config::get('mx_end_title_separator')); ?>">
                    </div>
                    <div class="form-group">
                        <?php submit_button(); ?>
                    </div>
                </form>
                <div class="element-info"><?php _e('Separators for MatrixSEO added words.', MatrixSeo_Utils::MATRIXSEO); ?></div>
                <br style="clear:both;">
            </div>
        </div>
        <div class="third">
            <div class="inside">
                <div class="title-sep-container">
                    <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/actions_files.png",__FILE__));?>">
                    <h2><?php _e('Internal Files', MatrixSeo_Utils::MATRIXSEO); ?></h2>
                </div>
                <p><?php _e('Delete Internal Data Files', MatrixSeo_Utils::MATRIXSEO); ?></p>
                <a href="#" id="delete_files" class="button button-success"><?php _e('Delete all files', MatrixSeo_Utils::MATRIXSEO); ?></a>
                <p><?php _e('Repopulate Actions Files From Database', MatrixSeo_Utils::MATRIXSEO); ?></p>
                <a href="#" id="repopulate-actions" class="button button-success"><?php _e('Repopulate', MatrixSeo_Utils::MATRIXSEO); ?></a>
                <div class="element-info"><?php _e('MatrixSEO files tools.', MatrixSeo_Utils::MATRIXSEO); ?></div>
                <br style="clear:both;">
            </div>
        </div>
        <div class="third">
            <div class="inside">
                <div class="title-sep-container sw-container">
                    <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/stop_words.png",__FILE__));?>">
                    <h2><?php _e('Stop Words', MatrixSeo_Utils::MATRIXSEO); ?></h2>
                    <h2><?php _e('Stop Words', MatrixSeo_Utils::MATRIXSEO); ?></h2>
                </div>
                <p><?php _e('Update Stop Words From API', MatrixSeo_Utils::MATRIXSEO); ?></p>
                <a href="#" id="update-stopwords" class="button button-success"><?php _e('Update', MatrixSeo_Utils::MATRIXSEO); ?></a>
                <div class="element-info"><?php _e('Update the stop-words list from API.', MatrixSeo_Utils::MATRIXSEO); ?></div>
                <br style="clear:both;">
            </div>
        </div>

        <div class="third">
            <div class="inside">
                <div class="title-sep-container titles-box">
                    <img src="<?php echo MatrixSeo_Utils::cleanURL(plugins_url("../img/signature.png",__FILE__));?>">
                    <h2><?php _e('Plugin Signature', MatrixSeo_Utils::MATRIXSEO); ?></h2>
                </div>
                <form method="post" action="<?php echo MatrixSeo_Utils::cleanURL(admin_url( "options-general.php?page=matrixseo&tab=advanced" )); ?>">
                    <div class="form-group">
                        <label for="mx_signature">
                            <input name="mx_signature" id="mx_signature" type="checkbox" value="1" <?php echo MatrixSeo_Config::get('mx_signature_active')==="1"?"checked":""; ?> style="display:inline-block;"/>
                            <?php _e('Plugin Signature', MatrixSeo_Utils::MATRIXSEO); ?>
                        </label>
                    </div>
                </form>
                <div class="element-info"><?php _e('Enable / disable the MatrixSEO signature.', MatrixSeo_Utils::MATRIXSEO); ?></div>
                <br style="clear:both;">
            </div>
        </div>

<?php endif; ?>
<br style="clear:both;">