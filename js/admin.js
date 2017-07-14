jQuery(document).ready(function() {
    jQuery('.lcih_import_wrap').fadeIn();
});

function doLCIHImportStep()
{
    jQuery.post(lcih_ajax_url, {
        action: 'do_import_step',
        count: mi_count,
        file_name: mi_file
    }, function (resp) {
        var data = eval('(' + resp + ')');
        if (data.status == 'ok')
        {
            jQuery('.lcih_progress').css('width', (mi_count + 1) * 100 / parseInt(data.total) + '%').html(Math.floor((mi_count + 1) * 100 / parseInt(data.total)) + '%');
            jQuery('.mi-msg').html(data.msg);
            if (typeof data.output !== 'undefined')
                jQuery('.mi-output').val(jQuery('.mi-output').val() + "\r\n" + data.output);
            mi_count++;
            if (mi_count < parseInt(data.total))
                doLCIHImportStep();
            else {
                jQuery('.mi-msg').html("Import completed!");
                jQuery('.lcih_progress_wrap').fadeOut();
            }

        } else
        {
            alert(data.msg);
            jQuery('.mi-msg').html("Error occured");
        }
    })
}