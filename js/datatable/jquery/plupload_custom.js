basehref = $('base').attr('href');
var uploader = []
var uploaderInited = [];
var createUploader = function(idBtn, multi_selection) {
    if (multi_selection) {
        multi_selection = true;
    } else {
        multi_selection = false;
    }
    uploader[idBtn] = new plupload.Uploader({
        runtimes : 'gears,html5,silverlight,flash,html4',
        multi_selection:multi_selection,     // <-this is what you needed
        browse_button : idBtn,
        max_file_size : '1000mb',
        chunk_size : '2mb',
        url : window.location.href + "&dt_action=upload&nomain=1",
        flash_swf_url : basehref + 'js/admin/plupload/plupload.flash.swf',
        silverlight_xap_url : basehref + 'js/admin/plupload/plupload.silverlight.xap',
        filters : [
        {
            title : "Image files", 
            extensions : "jpg,jpeg,gif,png"
        },

        {
            title : "Zip files", 
            extensions : "zip,rar,bz2"
        },

        {
            title : "Adobe", 
            extensions : "pdf,eps,psd,ai,indd"
        }
        ],
        drop_element : 'colright',
        unique_names : false,
        multiple_queues : true
    });
    
    var detailId = idBtn.split("_")
    detailId.shift()
    uploader[idBtn].name = detailId.join("_")
    uploaderInited[idBtn] = false
    uploaderInit(idBtn)
    
}
    

var uploaderInit = function(idBtn){
    if (!uploaderInited[idBtn]) {
        uploaderInited[idBtn] = true;

        uploader[idBtn].init();

        uploader[idBtn].bind('FilesAdded', function(up, files) {
            //            var file = files[0]
            //            // affichage à l'ajout avec <div class="progressbar"></div>
            //            
            //            file.div = $('<div>');
            //            $('#filelist').append(
            //                '<div id="' + file.id + '">' +
            //                file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
            //                '</div>');

            if (uploader[idBtn].settings.multi_selection == false) {
                $("#" + idBtn).parents(".control-group:first").find('.filelist').empty()
            }
            
            
            $("#" + idBtn).parents(".control-group:first").find('.filelist').removeClass("hide")
            $.each(files, function(i, file) {
                if (uploader[idBtn].settings.multi_selection == false) {
                    var infoField = uploader[idBtn].name.split("-")
                    var $myForm = $("#" + idBtn).parents("form");
                    console.log($("#" + infoField[1], $myForm))
                    $("#" + infoField[1], $myForm).val(file.name)
                }
                $("#" + idBtn).parents(".control-group:first").find('.filelist tbody').append(
                    '<tr id="' + file.id + '">' +
                    '<td>' + (i+1) + '</td><td>' + file.name + '</td><td>' + plupload.formatSize(file.size) + '</td>' +
                    '<td><a href="" class="remove btn btn-danger" title="Supprimer"><img width="12" src="img/back/white/trash_stroke_16x16.png" alt="Supprimer" /></a></td>' +
                    '</tr>');
                var $tr = 
                $('<tr id="' + file.id + '_progress">' +
                    '<td style="padding: 0;line-height:0"  colspan="4">' +
                    '    <div style="height:6px"  class="progress progress-striped active hide"><div class="bar" style=""></div></div>' + 
                    '</td>' +
                    '</tr>')
                $("#" + idBtn).parents(".control-group:first").find('.filelist tbody').append(
                    $tr
                    );
                file.div = $tr;
                
                $('#' + file.id + ' a.remove').first().click(function(e) {
                    e.preventDefault();
                    uploader[idBtn].removeFile(file);
                    $('#' + file.id + ',#' + file.id + "_progress").remove();
                    if (uploader[idBtn].files.length == 0) {
                        $("#" + idBtn).parents(".control-group:first").find('.filelist').addClass("hide");
                    }
                });
                
                
                
            });
            
            
            $('.bar').css({
                width: "0%"
            });

            uploader[idBtn].refresh();
        });

        uploader[idBtn].bind('UploadProgress', function(up, file) {
            $('.progress', file.div).removeClass("hide")
            $('.bar', file.div).css({
                width: file.percent + "%"
            });
        });

        uploader[idBtn].bind('Error', function(up, err) {
            err.file.error = true;
            up.refresh();
        });

    //        uploader[idBtn].bind('FileUploaded', function(up, file, info) {
    //
    //            $(file.div, '.progressbar').progressbar("destroy");
    //
    //            var response = $.parseJSON(info.response);
    //
    //            if(response.status != "error") {
    //                    
    //            }                
    //                
    //            uploader[idBtn].splice(0, 1);
    //                
    //        });
    }
    else
        uploader[idBtn].refresh();
}
    

