var oTable = null;

function reloadDatatable() {
    if (oTable != null) {
        oTable.fnDestroy();
    }

    $('#tableau').css({
        width: '100%'
    });

    oTable = $('#tableau').dataTable({
        bJQueryUI: true,
        aoColumns: [
            {
                bSortable: false
            },
            null,
            null,
            {
                bSortable: false
            }
        ],
        oLanguage: {
            sProcessing: 'Chargement...',
            sLengthMenu: 'Montrer _MENU_ fichiers par page',
            sZeroRecords: 'Aucun fichier trouvé',
            sEmptyTable: 'Pas de fichier',
            sInfo: 'fichiers _START_ à  _END_ sur _TOTAL_ fichiers',
            sInfoEmpty: 'Aucun fichier',
            sInfoFiltered: '(filtre sur _MAX_ fichiers)',
            sInfoPostFix: '',
            sSearch: '',
            sUrl: '',
            oPaginate: {
                sFirst: '',
                sPrevious: '',
                sNext: '',
                sLast: ''
            }
        }
    });

    $('.dataTables_filter input').attr('placeholder', 'Recherche...');
}

$(function(){
    var basehref = $('base').attr('href'),
        pluploaderParams = {
        basehref            : basehref,
        runtimes            : 'gears,html5,silverlight,flash,html4',
        max_file_size       : '1000mb',
        chunk_size          : '2mb',
        url                 : basehref + 'back/media/upload.html?id_gab_page=' + $('[name=id_gab_page]').val(),
        flash_swf_url       : basehref + 'app/back/js/plupload/plupload.flash.swf',
        silverlight_xap_url : basehref + 'app/back/js/plupload/plupload.silverlight.xap',
        drop_element        : '#uploader_popup',
        filters             : [
            {
                title: 'Image files',
                extensions: 'jpg,jpeg,gif,png'
            },
            {
                title: 'Zip files',
                extensions: 'zip,rar,bz2'
            },
            {
                title: 'Adobe',
                extensions: 'pdf,eps,psd,ai,indd'
            },
            {
                title: 'Fichiers vidéos',
                extensions: 'mp4'
            }
        ],
        unique_names        : false,
        multiple_queues     : true,
        FilesAdded          : function(base, up, files) {
            $.each(files, function(i, file) {
                var tr, td;
                if (!file.error) {
                    tr = $('<tr>');
                    $('<td>', {
                        colspan: 4
                    }).html(file.name + '<div style="height:6px"  class="progress progress-striped active hide"><div class="bar" style=""></div></div>').appendTo(tr);
                    file.tr = tr;
                }
                else
                    uploader.splice(i, 1);
            });

            $.each(files, function(i, file) {
                if (!file.error) {
                    if (i == 0) {
                        file.tr.prependTo($('#foldercontent'));
                    } else {
                        file.tr.insertAfter(files[i - 1].tr);
                    }
                }
            });

            $('.bar').css({
                width: '0%'
            });

            up.refresh();
            up.start();
        },
        UploadProgress      : function(base, up, file) {
            $('.progress', file.tr).removeClass('hide')
            $('.bar', file.tr).css({
                width: file.percent + '%'
            });
        },
        FileUploaded        : function(base, up, file, info) {
                $(file.tr, '.progressbar').progressbar('destroy');

                var response = $.parseJSON(info.response);

                if (response.status != 'error') {
                    if ('id_temp' in response) {
                        $('input[name=id_temp]:first').val(response.id_temp);
                        $.cookie('id_temp', response.id_temp, {
                            path: '/'
                        });
                    }

                    $('.atelecharger-' + file.id).val(response.filename);

                    var ligne = '';

                    ligne += '<td><a href="' + response.url + '" id="fileid_' + response.id + '" target="_blank" class="previsu">';

                    var ext = file.name.split('.').pop().toLowerCase();
                    if ($.inArray(ext, extensionsImage) != -1) {
                        ligne += '<img class="vignette img-polaroid" src="' + response.mini_url + '" alt="' + ext + '" /></a></td>';
                    } else {
                        ligne += '<img class="vignette" src="app/back/img/filetype/' + ext + '.png" alt="' + ext + '" /></a></td>';
                    }

                    ligne += '<td>' + response.size + '</td>';
                    ligne += '<td>' + response.date.substr(0, 10) + '<br />' + response.date.substr(11) + '</td>';
                    ligne += '<td><div class="btn-group">';
                    ligne += '<a title="Visualiser" target="_blank"  class="btn btn-info previsu" href="' + response.path + '"><i class="icon-camera"></i></a>';
                    ligne += '</div></td>';

                    file.tr.attr('id', 'fileid_' + response.id);
                    file.tr.html(ligne);
                }
                else {
                    file.tr.remove();
                }

                up.splice(0, 1);
                rescale()

                if (up.files.length == 0) {
                    reloadDatatable();
                }
            },
            Error           : function(base, up, err) {
                err.file.error = true;
                up.refresh();
            }
        };

    $.cookie('id_temp', 0, {
        path: '/'
    });

    $('<div>', {
        id: 'uploader_popup'
    }).load('back/media/popuplistefichiers.html?id_gab_page=' + $('[name=id_gab_page]').val(), function() {
        var self = $(this),
            heading = 'Importer des fichiers',
            closeButtonTxt = 'Fermer';
        $('.uploader_popup').click(function(e) {
            e.preventDefault();
            myModal.message(heading, self, closeButtonTxt);
            $('#uploader_popup').parents('modal');

            if (oTable == null) {
                reloadDatatable();
            }

            $('#pickfiles').pluploader(pluploaderParams);
            $('#pickfiles').live('click', function(e) {
                e.preventDefault();
            });
        });
    });
});

