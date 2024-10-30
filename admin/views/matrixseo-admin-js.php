<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
/* Allow edit of ips */
$("#allow_edit_ips").change(function() {
	if(this.checked) {
		$('#ips').removeAttr('disabled');
	}else{
		$('#ips').attr('disabled', 'disabled');
	}
});

/* Allow edit of refs */
$("#allow_edit_refs").change(function() {
	if(this.checked) {
		$('#referers').removeAttr('disabled');
	}else{
		$('#referers').attr('disabled', 'disabled');
	}
});

/* Search actions */
$('#sForm').on('submit', function(e){
    e.preventDefault();
    window.location.href = window.location.href + "options-general.php?page=matrixseo&tab=actions&searchurl=" + $("#url-term").val();
    return false;
});

/* Adapt the cron height */
var h = window.innerHeight;
$('#cronContainer').css('height', (h-280)+'px');


/* Arrange notices in case of many */
var noticesNo = $('.msnotice').length;

$('.msnotice').each(function(item){
    if(noticesNo > 1 && item > 0){
        if(noticesNo > 2){
            if(item == 1){
                $(this).css('top', item * '85' + 'px');
            }else if(item == 2){
                $(this).css('top', item * '75' + 'px');
            }

        }else{
            if(item == 0){
                $(this).css('top', '75' + 'px');
            }else{
                $(this).css('top', '5' + 'px');
            }
        }
    }else{
        if(noticesNo == 1){
            $(this).css('top', '5' + 'px');
        }else{
            if(item == 0){
                $(this).css('top', '75' + 'px');
            }else{
                $(this).css('top', '5' + 'px');
            }
        }

    }

    var $this = $(this);
    setTimeout(function(){
        $this.hide("slow");
    }, 5000);
});

/* Hide the notice on click */
$('.msnotice').on('click', function(){
    $(this).hide();
});

/* OPEN / CLOSE the hamburger */
$('.hamburger').on('click', function(e){
    e.preventDefault();
    if( $('.nav-tab-wrapper').hasClass('show') ){
        //show
        $('.nav-tab-wrapper').removeClass('show');
        $('.nav-tab-wrapper').css('display', 'none');
    }else{
        //hide
        $('.nav-tab-wrapper').addClass('show');
        $('.nav-tab-wrapper').css('display', 'block');
    }
    return false;
});

/* Change debug level */
$('#debug_level').on('change', function(e){
    e.preventDefault();
    var lvl = $(this).val();
    var data = {
        'action'    : 'matrixseo_ajax_actions',
		'what'      : 'debug_level',
        'level'     : $(this).val()
    };
    jQuery.post(ajaxurl, data, function(response) {
        mxAlert("Debug level changed.");
        if(lvl == '3'){
            hideDebugLevelMsg();
        }else{
            $('#lvlMax').addClass('hideDebugLevel');
        }

    });
    return false;
});

/* Clear debug log */
$('#clearLog').on('click', function(e){
    e.preventDefault();
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'clear_log'
    };
    jQuery.post(ajaxurl, data, function(response) {
        $('#cronContainer').val('');
        mxAlert('Log file cleared.');
        $('#debug-size').text('0B');
    } );

    return false;
});

/* Enable / disable signature */
$('#mx_signature').change(function(){
    var tmpValue="0";
    if(this.checked){
        tmpValue="1";
    }
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'change_signature',
        'value'     : tmpValue
    };
    jQuery.post(ajaxurl, data, function(){
        if(tmpValue=='1'){
            mxAlert("Signature activated");
        }else{
            mxAlert("Signature deactivated");
        }
    });
});

/* Ignore action */
$('.action_item').on('click', function(e){
    e.preventDefault();
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'ignore_action',
        'value'     : $(this).data('id')
    };
    jQuery.post(ajaxurl, data, function(){
        window.location.reload();
    });
    return false;
});

/* Apply action */
$('.remove_ig_item').on('click', function(e){
    e.preventDefault();
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'apply_action',
        'value'     : $(this).data('id')
    };
    jQuery.post(ajaxurl, data, function(){
        window.location.reload();
    });
    return false;
});

/* Enable / disable cron debug */
$('#activateLogs').on('change', function(){
    var tmpValue="0";
    var tmpWord="deactivated";
    if(this.checked){
        tmpValue="1";
        tmpWord="activated";
    }
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'activate_debug',
        'value'     : tmpValue
    };
    jQuery.post(ajaxurl, data, function(){
        $('#debug-tab').toggle();

        mxAlert("Debug " + tmpWord);
    });
});

/* Enable / disable plugin */
$('.activator').on('click', function(e){
    var tmpValue='0';
    var tmpWord="deactivated";
    if ( $(this).val() == 1 ){
        tmpValue='1';
        tmpWord="activated";
    }
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'activate_plugin',
        'value'     : tmpValue
    };
    jQuery.post(ajaxurl, data, function(response){
        if (tmpValue == '1'){
            $('.ms-auto-control .notice-dismiss').click();
        }
        mxAlert("MatrixSEO has been " + tmpWord + ".");
        tmp=JSON.parse(response);
        if(tmp.reload==true){
            window.location.reload();
        }
    });
});

/* Refresh debug log */
function refreshLogs(){
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'debug_log'
    };
    jQuery.post(ajaxurl, data, function(response){
        var tmp=JSON.parse(response);
        $('#cronContainer').val(tmp.debug);
        $('#debug-size').text(tmp.size);
    });
}

/* Change debug level */
$('#delete_files').on('click', function(e){
    e.preventDefault();

    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'delete_files'
    };

    jQuery.post(ajaxurl, data, function(response) {
        mxAlert('Files deleted.');
        console.log('here');
    });
    return false;
});

<?php
if(isset($_GET['tab']) && $_GET['tab']=="debug")
{
?>
setInterval(function(){
    refreshLogs();
}, 10000);
<?php
}
?>

/* Alert */
var timeout;

function mxAlert(msg){
    $('#wpbody-content').prepend('<div class="msnotice msnotice-success notice notice-success"><img src="../wp-content/plugins/matrixseo/admin/img/success.png">&nbsp;<span>'+msg+'</span></div>');
    hideNotices();
    arrangeNotices();
    hideNoticeOnClick();
}

function arrangeNotices(){
    var noticesNo = $('.msnotice').length;
    $('.msnotice').each(function(id, item){
        $(this).css('top', id * 70 + 'px');
    });

    if(noticesNo > 1){
        hideNotices();
    }
}

function hideNotices(){
    clearTimeout(timeout);
    timeout = setTimeout(function(){$('.msnotice').hide('slow');},5000);
}

function hideNoticeOnClick(){
    $('.msnotice').on('click', function(){
        $(this).hide();
        $(this).removeClass('msnotice');
        arrangeNotices();
    })
}

function hideDebugLevelMsg(){
    var html = '<div class="ms-alert ms-alert-danger" id="lvlMax"><?php _e('(Current debug file size: <b>'.MatrixSeo_Utils::humanFilesize(filesize(MatrixSeo_Utils::getStorageDirectory("debug.php"))).'</b>)', MatrixSeo_Utils::MATRIXSEO); ?></div>';
    if( $('#lvlMax').length == 0 ){
        $('#debugLvl').append(html);
    }else{
        $('#lvlMax').removeClass('hideDebugLevel');
    }
}

/* Repopulate settings */
$('#repopulate-settings').on('click', function(event){
    event.preventDefault();
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'repopulate-settings'
    };
    jQuery.post(ajaxurl, data, function(response){
        tmp=JSON.parse(response);
        if(tmp.ips){
            $('#ips').val(tmp.ips);
        }
        if(tmp.referers){
            $('#referers').val(tmp.referers);
        }
    });
    mxAlert("Settings repopulated.");
    return false;
});


/* Repopulate actions */
$('#repopulate-actions').on('click',function(event){
    event.preventDefault();
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'repopulate-actions'
    };
    jQuery.post(ajaxurl, data, function(response) {
        mxAlert("Actions repopulated.");
    });
    return false;
});

/* Update stopwords*/
$('#update-stopwords').on('click',function(event){
    event.preventDefault();
    var data = {
        'action'    : 'matrixseo_ajax_actions',
        'what'      : 'update-stopwords'
    };
    jQuery.post(ajaxurl, data, function(response) {
        mxAlert("Stopwords updated.");
    });
    return false;
});