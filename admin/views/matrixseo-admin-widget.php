<style>
    .mx_widget_css h2{
        font-size:14px !important;
        font-weight: bold !important;
    }
    .mx_widget_css ul{
        display: block;
        height:10px;
        border-top:1px solid #EEE;
        padding-top:10px;
    }
    .mx_widget_css ul li{
        float:left;
        display: block;
        margin-right:20px;
        padding-right:20px;
        border-right:1px solid #EEE;
    }
</style>
<div class="mx_widget_css">
    <div class="wrap">
        <h2><?php _e('Search engine visits', MatrixSeo_Utils::MATRIXSEO); ?></h2>
        <p><?php _e("Search engine visits: ", MatrixSeo_Utils::MATRIXSEO); echo MatrixSeo_Config::get('mx_total_se'); ?></p>
    </div>

    <div class="wrap">
        <h2><?php _e('Visitors', MatrixSeo_Utils::MATRIXSEO); ?></h2>
        <p><?php _e('Visitors from search engines: ', MatrixSeo_Utils::MATRIXSEO); echo MatrixSeo_Config::get('mx_total_ref'); ?></p>
    </div>

    <div class="wrap">
        <h2><?php _e('Actions', MatrixSeo_Utils::MATRIXSEO); ?></h2>
        <p><?php _e("Actions in the last 30 days: ", MatrixSeo_Utils::MATRIXSEO); echo MatrixSeo_Config::get('mx_total_act') != false ? get_option('mx_total_act') : 0; ?></p>
    </div>


    <ul >
       <li><a href="options-general.php?page=matrixseo&tab=settings">Settings</a></li>
        <li><a href="options-general.php?page=matrixseo&tab=actions">Actions</a></li>
    </ul>
</div>