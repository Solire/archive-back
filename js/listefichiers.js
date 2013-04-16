var tree, uploader, oTable = null, nomdesobjets='fichier';
var basehref = ''
var extensionsImage = ['jpg', 'jpeg', 'gif', 'png'];
var image, contenu, resid = null, restype, currentData;
var oTable = null;

var orderby = {
    champ:"date_crea",
    sens:"desc"
};
var orderstates = ["", "asc", "desc", ""];
var orderclasses = ["ui-icon-carat-2-n-s", "ui-icon-carat-1-n", "ui-icon-carat-1-s", "ui-icon-carat-2-n-s"]



/**
 * Suppression des fichiers
 */
$(".delete-file").live("click", function (e) {
    e.preventDefault()
    var tr = $(this).parents('tr').first();
    if (confirm("Voulez-vous vraiment supprimmer ce fichier ? ")) {
        $.post('back/media/delete.html', {
            id_media_fichier : tr.attr('id').split('_').pop()
        }, function(data){
            if(data.status == 'success'){
                tr.fadeOut(500, function(){
                    $(this).remove()
                });
            }
        },'json');
    }
})


function reloadDatatable(data) {
    if(oTable != null) {
        oTable.fnDestroy();
    }

    $('#foldercontent').html(data);

    $("#tableau").css({
        width : "100%"
    })
    oTable = $("#tableau").dataTable({
        "bJQueryUI": true,
        "aoColumns": [
        {
            "bSortable": false
        },
        null,
        null,
        null,
        null,
        null,
        {
            "bSortable": false
        },
        ],
        'oLanguage': {
            "sProcessing": "Chargement...",
            "sLengthMenu": "Montrer _MENU_ fichiers par page",
            "sZeroRecords": "Aucun fichier trouvé",
            "sEmptyTable": "Pas de fichier",
            "sInfo": "fichiers _START_ à  _END_ sur _TOTAL_ fichiers",
            "sInfoEmpty": "Aucun fichier",
            "sInfoFiltered": "(filtre sur _MAX_ fichiers)",
            "sInfoPostFix": "",
            "sSearch": "",
            "sUrl": "",
            "oPaginate": {
                "sFirst": "",
                "sPrevious": "",
                "sNext": "",
                "sLast": ""
            }
        }
    } )
    $('.dataTables_filter input').attr("placeholder", "Recherche...");
}


$(function () {



    /**
     * Titre trop long (scroll)
     */
    jQuery.fn.scroller = function () {
        $(this).SetScroller({
            velocity: 	 60,
            direction: 	 'horizontal',
            startfrom: 	 'right',
            loop:	 'infinite',
            movetype: 	 'linear',
            onmouseover: 'pause',
            onmouseout:  'pause',
            onstartup: 	 'pause',
            cursor: 	 'pointer'
        });

        $(this).unbind("mouseover");
        $(this).unbind("mouseout");
        //how to play or stop scrolling animation outside the scroller...
        $(this).mouseenter(function(){
            if($('.scrollingtext', this).width() > $(this).width())$(this).PlayScroller();
        });
        $(this).mouseleave(function(){
            $(this).PauseScroller();
            $('.scrollingtext', this).css("left","0px");
        });

        $(' .scrollingtext', this).css("left","0px");
        return this;
    }



    $( ".horizontal_scroller" ).livequery(function() {
        var newHeight = 0, $this = $( this );
        $.each( $this.children(), function() {
            newHeight += $( this ).height();
        });
        $this.height( newHeight );
        $this.scroller();

    });






    basehref = $('base').attr('href');

    reloadDatatable("")

    //////////////////// JSTREE ////////////////////
    tree = $("#folders").jstree({
        "plugins" : ["themes", "json_data", "ui", "crrm", "cookies", "search", "types", "hotkeys"],//, "contextmenu"
        "core" : {
            "html_titles" : true
        },
        "json_data" : {
            "ajax" : {
                "url" : "back/media/folderlist.html",
                "data" : function (n) {
//                    console.log(n)
                    return {
                        "id" : n.attr ? n.attr("id").replace("node_","") : ""
                    };
                }
            }
        },
        "types" : {
            "max_depth" : -2,
            "max_children" : -2,
            "valid_children" : "root",
            "types" : {
                "root" : {
                    "valid_children" : ["page"]
                },
                "page" : {
                    "valid_children" : "none"
                }
            }
        }
    })
    .bind("select_node.jstree", function(e, data) {
        resid = data.rslt.obj.attr("id").replace("node_","");
        restype = data.rslt.obj.attr("rel");

        $.cookie('id_gab_page', resid, {
            path : '/'
        });

        tree.jstree("open_node", data.rslt.obj);

        if (restype == "page") {
            $('#pickfiles').fadeIn(200);
            $('#foldercontent').html('<tr><td colspan="6">chargement ... </td></tr>');

            $.post(
                "back/media/list.html",
                {
                    id_gab_page: resid,
                    search: $('#search').val(),
                    orderby: orderby
                },
                function(data){

                    reloadDatatable(data)

                }
                );
        }
        else {
            reloadDatatable("")
            $('#pickfiles').fadeOut(200);
        }
    });
    //////////////////// FIN JSTREE ////////////////////


    //////////////////// PLUPLOAD ////////////////////
    uploader = new plupload.Uploader({
        runtimes : 'gears,html5,silverlight,flash,html4',
        browse_button : 'pickfiles',
        max_file_size : '1000mb',
        chunk_size : '2mb',
        url : basehref + 'back/media/upload.html',
        flash_swf_url : basehref + 'back/plupload/plupload.flash.swf',
        silverlight_xap_url : basehref + 'back/plupload/plupload.silverlight.xap',
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
        },
        {
            title : "Fichiers vidéos",
            extensions : "mp4"
        }
        ],
        drop_element : 'colright',
        unique_names : false,
        multiple_queues : true
    });

    uploader.bind('Init', function(up, params) {
        $('#currentruntime').text("Current runtime: " + params.runtime);
    });

    uploader.init();

    uploader.bind('FilesAdded', function(up, files) {
        if (restype == "page") {
            $.each(files, function(i, file) {
                var tr, td;
                if(!file.error) {
                    tr = $('<tr>').addClass("filenotused");
                    $('<td>', {
                        colspan : 4
                    }).html(file.name + '<div class="progressbar"></div>').appendTo(tr);
                    file.tr = tr;
                }
            });

            $.each(files, function(i, file) {
                if (i==0)
                    file.tr.prependTo($('#foldercontent'));
                else
                    file.tr.insertAfter(files[i-1].tr);
            });

            $('.progressbar').progressbar({
                value: 0
            });

            up.refresh();
            up.start();
        }
        else
            uploader.splice(0, uploader.files.length);
    });

    uploader.bind('UploadProgress', function(up, file) {
        $(file.tr, '.progressbar').progressbar("value", file.percent);
    });

    uploader.bind('Error', function(up, err) {
        err.file.error = true;
        up.refresh(); // Reposition Flash/Silverlight
    });

    uploader.bind('FileUploaded', function(up, file, info) {
        $(file.tr, '.progressbar').progressbar("destroy");

        var response = $.parseJSON(info.response);

        if(response.status != "error") {
            var ligne = '';

            ligne += '<td><a href="' + response.url + '" id="fileid_' + response.id + '" target="_blank" class="previsu">';

            var ext = file.name.split('.').pop().toLowerCase();
            if (extensionsImage.indexOf(ext) != -1)
                ligne += '<img class="vignette" src="' + response.mini_url + '" alt="' + ext + '" /></a></td>';
            else
                ligne += '<img class="vignette" src="img/back/' + ext + '.png" alt="' + ext + '" /></a></td>';


            ligne += '<td>' + response.size + '</td>';
            ligne += '<td>' + response.width + '</td>';
            ligne += '<td>' + response.height + '</td>';
            ligne += '<td>' + response.date.substr(0, 10) + '<br />' + response.date.substr(11) + '</td>';
            ligne += '<td>Non</td>';
            ligne += '<td><div class="btn-a gradient-blue"><a class="previsu" href="' + response.path + '"><img src="app/back/img/voir.png" alt="Prévisualisation"></a></div>';
            ligne += '<div class="btn-a gradient-blue"><a class="delete-file" href="#"><img src="app/back/img/white/trash_stroke_16x16.png" alt="Supprimer"></a></div></td>'


            file.tr.attr("id", "fileid_" + response.id);
            file.tr.html(ligne);
        }
    });

    image = $(null);


    var previsu = $('<div>', {
        id: 'previsu'
    }).dialog({
        title : "Prévisualisation",
        buttons: [
        {
            text : "Supprimer",
            click : function(){
                var tr = image.parents('tr').first();
                $.post('back/media/delete.html', {
                    id_media_fichier : tr.attr('id').split('_').pop()
                }, function(data){
                    if(data.status == 'success'){
                        tr.fadeOut(500, function(){
                            $(this).remove()
                        });
                    }
                },'json');
                $(this).dialog("close");
            }
        },
        {
            text : "Annuler",
            click : function(){
                $(this).dialog("close");
            }
        }
        ],
        autoOpen: false,
        close: function(event, ui){
            image = $(null);
        },
        height: "auto",
        width: "auto",
        maxHeight : $(window).height()-230,
        maxWidth : $(window).width()-180
    }).css({
        "max-height" : $(window).height()-230,
        "max-width" : $(window).width()-180
    });

    $('.previsu').live('click', function(){
        previsu.dialog('close');
        image = $(this);
        var link = $(this).attr('href');
        var ext = link.split('.').pop().toLowerCase();
        if (extensionsImage.indexOf(ext) != -1) {
            $('<img>', {
                'src' : link
            }).load(function(){
                if (extensionsImage.indexOf(ext) != -1) {
                    previsu.dialog( "option" , "height" , "auto" );
                    previsu.dialog( "option" , "maxWidth" , $(window).width()-180 );
                    previsu.dialog( "option" , "maxHeight" , $(window).height()-230 );

                    previsu.html(this);
                }
                else {
                    previsu.dialog( "option" , "height" , 0 );
                    previsu.html('');
                }

                previsu.dialog('open');
                previsu.dialog('option', 'position', "center");
            });

            return false;
        }
    });

    $('#search').keyup(function(){
        $('#node_' + resid + ' > a').click()
    });


});