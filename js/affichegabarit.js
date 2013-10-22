var extensionsImage = ['jpg', 'jpeg', 'gif', 'png'];
var sort_elmt = $(null);
var sortpar = $(null);
var basehref = '';

function addMarker(map3, lat, lng) {
    map3.gmap3({
        marker: {
            tag: 'myMarker',
            callback: function(marker) {
                map3.data('marker', marker)
                $('.gmap-marker-button', map3.data('btn-marker')).attr('title', 'Cliquez pour supprimer le marqueur')
                $('.gmap-marker-button div strong', map3.data('btn-marker')).html('Supprimer le marqueur')
                $('input.gmap_lat', map3.parents('.line:first')).val(marker.position.lat());
                $('input.gmap_lng', map3.parents('.line:first')).val(marker.position.lng());
                $('input.gmap_zoom', map3.parents('.line:first')).val(map3.gmap3('get').getZoom());
            },
            latLng: [lat, lng],
            options: {
                draggable: true,
                animation: google.maps.Animation.DROP
            },
            events: {
                dragend: function(marker) {
                    $('input.gmap_lat', map3.parents('.line:first')).val(marker.position.lat());
                    $('input.gmap_lng', map3.parents('.line:first')).val(marker.position.lng());
                }
            }
        }
    })
}

function removeMarker(map3) {
    map3.gmap3({
        clear: {
            callback: function() {
                map3.data('marker', null)
                $('.gmap-marker-button', map3.data('btn-marker')).attr('title', 'Cliquez pour ajouter un marqueur')
                $('.gmap-marker-button div strong', map3.data('btn-marker')).html('Ajouter un marqueur')
                $('input.gmap_lat', map3.parents('.line:first')).val('');
                $('input.gmap_lng', map3.parents('.line:first')).val('');
                $('input.gmap_zoom', map3.parents('.line:first')).val('');
            },
            tag: 'myMarker'
        }

    })
}

tinymce.init({
    mode                : 'none',
    language            : 'fr_FR',

//    width               :'100%',
    height              :'290px',
    entity_encoding     : 'raw',

    plugins             : [
        'advlist autolink lists link image charmap print preview anchor',
        'searchreplace visualblocks fullscreen',
        'insertdatetime media contextmenu paste'
    ],
    menubar             : false,
    statusbar           : false,
    toolbar             : 'insertfile undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image',

//    relative_urls       : true,
//    convert_urls        : true,
    document_base_url   : '../../../../',
    image_list          : 'back/media/autocomplete.html?tinyMCE',
    link_list           : 'sitemap.xml?visible=0&json=1&onlylink=1&tinymce=1'
});

$(function() {
    $.cookie('id_gab_page', $('input[name=id_gab_page]').val(), {
        path: '/'
    });

    /**
     * Redimensionnement et recadrage des images
     */
    // Create variables (in this scope) to hold the API and image size
    var jcrop_api, boundx, boundy, $inputFile, $inputAlt;

    function updatePreview(c)
    {
        if (parseInt(c.w) > 0)
        {
            var rx = 100 / c.w;
            var ry = 100 / c.h;

            $('#crop-preview').css({
                width: Math.round(rx * boundx) + 'px',
                height: Math.round(ry * boundy) + 'px',
                marginLeft: '-' + Math.round(rx * c.x) + 'px',
                marginTop: '-' + Math.round(ry * c.y) + 'px'
            });

            $('.form-crop-submit').removeClass('disabled')

            updateCoords(c);
        }
    }

    function updateCoords(c)
    {
        $('#x').val(c.x);
        $('#y').val(c.y);
        $('#w').val(c.w);
        $('#h').val(c.h);
        $('.wShow').val(Math.round(c.w));
        $('.hShow').val(Math.round(c.h));
    }

    $('#modalCrop').modal({
        show: false,
        backdrop: true,
        keyboard: true
    }).addClass('modal-big');

    $('.wShow, .hShow').bind('change', function() {

        var w = parseInt($('.wShow').val());
        var h = parseInt($('.hShow').val());
        var x = parseInt($('#x').val());
        var y = parseInt($('#y').val());
        if (isNaN(x)) {
            x = 0;
        }

        if (isNaN(y)) {
            y = 0;
        }
        jcrop_api.setSelect([x, y, x + w, y + h]);
    });

    $('.spinner').spinner({
        min: 0
    });

    $('.back-to-list').click(function(e) {
        e.preventDefault()
        var heading = 'Quitter';
        var question = 'Attention, les données saisies ne seront pas sauvegardées, malgré cela êtes-vous sûr de vouloir quitter cette page ? ';
        var cancelButtonTxt = 'Annuler';
        var okButtonTxt = 'Confirmer';
        var href = $(this).attr('href');
        var callback = function() {
            document.location.href = href;
        }

        myModal.confirm(heading, question, cancelButtonTxt, okButtonTxt, callback);


    })

    $('.form-crop-ok').bind('click', function() {
        $inputAlt.val($('#image-alt').val());
        $('#modalCrop').modal('hide');
    });

    $('.form-crop-submit').bind('click', function() {
        if ($(this).hasClass('disabled'))
            return false;
        var action = 'back/' + $('.form-crop').attr('action');
        var data = $('.form-crop').serialize();
        $.post(action, data, function(response) {
            $('#modalCrop').modal('hide');
            $inputFile.val(response.filename);
            $inputFile.siblings('.previsu').attr('href', response.path);
        }, 'json');
        $inputAlt.val($('#image-alt').val());
    });

    $('.crop').live('click', function(e) {
        e.preventDefault();
        openCropDialog.call(this);
    });

    var openCropDialog = function(){
        var aspectRatio = 0,
            visuelId,
            fieldset;

        $('.img-info, .expected-width, .expected-height, .expected-width-height').hide();
        $('.force-selection input').attr('checked', 'checked');

        $('.wShow').html('');
        $('.hShow').html('');

        var src = $(this).siblings('.previsu').attr('href');

        $inputFile = $(this).siblings('.form-file');

        visuelId = $inputFile.attr('data-visuel-id');
        fieldset = $inputFile.parents('fieldset:first');
        $inputAlt = $('input[type=hidden][data-visuel-id=' + visuelId + ']', fieldset);

        var $overlay = $('<div class="loading-overlay"><div class="circle"></div><div class="circle1"></div></div>').hide();
        $('body').prepend($overlay);
        var marginTop = Math.floor(($overlay.height() - $overlay.find('.circle').height()) / 2);
        $overlay.find('.circle').css({
            'margin-top': marginTop + 'px'
        });
        $overlay.fadeIn(500);

        $('<img>', {
            src: src
        }).load(function() {
            $('div.loading-overlay').remove();
            $('.form-crop-submit').addClass('disabled')

            var minWidth = $inputFile.attr('data-min-width');
            var minHeight = $inputFile.attr('data-min-height');
            $('.spinner').spinner('destroy');
            $('.spinner.wShow').spinner({
                min: minWidth
            });
            $('.spinner.hShow').spinner({
                min: minHeight
            });
            if (parseInt(minWidth) > 0) {
                $('#minwidthShow').html(minWidth);
                $('.img-info, .expected-width').show();
                $('.expected-width').find('input').attr('checked', 'checked');
            }

            if (parseInt(minHeight) > 0) {
                $('#minheightShow').html(minHeight);
                $('.img-info, .expected-height').show();
                $('.expected-height').find('input').attr('checked', 'checked');
            }

            if (parseInt(minHeight) > 0 && parseInt(minWidth) > 0) {
                $('#minheightShow').html(minHeight);
                $('.expected-width-height').show();
                $('.expected-width-height').find('input').attr('checked', 'checked');
                $('label.expected-width').hide();
                $('label.expected-height').hide();
                aspectRatio = minWidth / minHeight;
            }

            $('#minwidth').val(minWidth);
            $('#minheight').val(minHeight);
            var imageNameInfos = $inputFile.val().split('.');
            var imageExtension = imageNameInfos.pop();
            var imageName = imageNameInfos.join('');
            var imageAlt = $inputAlt.val();

            $('#image-alt').val(imageAlt);
            $('#image-name').val(imageName);
            $('#image-extension').val(imageExtension);

            $('#modalCrop table tr:first td:first').html('<img src="" class="img-polaroid" id="crop-target" alt="" />');
            $('#modalCrop #filepath').val(src);
            $('#crop-target').add('#crop-preview').attr('src', src);
            $('.jcrop-holder').remove();
            $('#modalCrop').appendTo('body')

            $('#crop-target').Jcrop({
                minSize: [minWidth, minHeight],
                boxWidth: 540,
                boxHeight: 400,
                onChange: updatePreview,
                onSelect: updatePreview,
                aspectRatio: aspectRatio
            }, function() {
                // Use the API to get the real image size
                var bounds = this.getBounds();
                boundx = bounds[0];
                boundy = bounds[1];
                // Store the API in the jcrop_api variable
                jcrop_api = this;
            });

            $('#modalCrop').modal('show');
        });
    };

    /**
     * Popup apres sauvegarde de la page
     */
    $('#modalMore').modal();

    $.datepicker.regional['fr'] = {
        closeText: 'Fermer',
        prevText: 'Précédent',
        nextText: 'Suivant',
        currentText: 'Aujourd\'hui',
        monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
        monthNamesShort: ['Janv.', 'Févr.', 'Mars', 'Avril', 'Mai', 'Juin',
            'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'],
        dayNames: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
        dayNamesShort: ['Dim.', 'Lun.', 'Mar.', 'Mer.', 'Jeu.', 'Ven.', 'Sam.'],
        dayNamesMin: ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
        weekHeader: 'Sem.',
        dateFormat: 'dd/mm/yy',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: ''
    };

    $.fn.clearForm = function() {
        var idnew;
        this.find('.token-input-list').remove();
        this.find('input, textarea, select').not('[name="visible[]"]').not('.join-param').not('.extensions').each(function() {
            idnew = $(this).attr('id') + 'a';
            $(this).attr('id', idnew);
            $(this).prev('label').attr('for', idnew);

            if ($(this).is('input'))
                $(this).val('');
            else {
                if ($(this).is('textarea')) {
                    $(this).tinymce('disable');
                    $(this).val('');
                }
                else {
                    if ($(this).is('select'))
                        $(this).val($(this).children('option:first').val());
                }
            }
        });

        this.find('.previsu').attr('href', '');
        this.find('.previsu').hide();
        this.find('.crop').hide();

        return this;
    };

    $('textarea.tiny').tinymce('enable');

    $('.switch-editor a').live('click', function(e) {
        e.preventDefault();

        var textarea = $(this).parent().nextAll('textarea');

        if ($(this).hasClass('btn-default') && textarea.length > 0) {
            textarea.tinymce('change');
            $(this).removeClass('btn-default').addClass('btn-info');
            $(this).siblings().removeClass('btn-info').addClass('btn-default');
        }
    });

    $('.sort-move').live('click', function(e) {
        e.preventDefault()
    })

    $('.sort-box').each(function() {
        $(this).sortable({
            placeholder: 'empty',
            items: '.sort-elmt',
            handle: '.sort-move',
            start: function(e, ui) {
                $('textarea', ui.item).tinymce('disable');
            },
            stop: function(e, ui) {
                $('textarea.tinymce-tmp-disabled', ui.item).tinymce('enable');
            }
        });
    });

    var $delBtnClone = $('.delBloc:first').clone();
    $('.delBloc.to-remove').remove();

    var $sortBtnClone = $('.sort-move:first').clone();
    $('.sort-move.to-remove').remove();

    $('.addBloc').live('click', function(e) {
        e.preventDefault();

        var $this = $(this);
        var adupliquer = $this.prev();
        $('textarea.tiny', adupliquer).tinymce('disable');
        var clone = adupliquer.clone(false).clearForm();
        clone.find('ul').remove();
        clone.insertBefore($this);
        clone.find('legend').html('Bloc en cours de création');
        $this.parents('.sort-box').sortable('refresh');
        $this.siblings('.sort-elmt').find('.btn-bloc-action').each(function() {
            if ($(this).find('.delBloc').length == 0)
                $(this).append($delBtnClone.clone())
            if ($(this).find('.sort-move').length == 0)
                $(this).prepend($sortBtnClone.clone())
        });

        $('.form-date', clone).removeClass('hasDatepicker').val('').datepicker($.datepicker.regional['fr']);

        initAutocompletePat(clone);
//        $('textarea', clone).autogrow({
//            minHeight: 150
//        });
        $('textarea.tiny', adupliquer).tinymce('enable');
        $('textarea.tiny', clone).tinymce('enable');
    });

    $('.301-add').live('click', function(e) {
        e.preventDefault();

        var $this = $(this).parents('fieldset:first').find('.line:first');
        var $fieldSet301 = $(this).parents('fieldset:first');
        var adupliquer = $this;
        var clone = adupliquer.clone(false).clearForm();
        $('.301-remove', clone).removeClass('translucide');
        clone.insertAfter($(this).parents('fieldset:first').find('.line:last'));
        if ($('.301-remove', $fieldSet301).length > 1) {
            $('.301-remove', $fieldSet301).removeClass('translucide');
        }
    });

    $('.301-remove:not(.translucide)').live('click', function(e) {
        e.preventDefault();

        var $this = $(this).parents('.line:first');
        var $fieldSet301 = $(this).parents('fieldset:first');
        $this.remove();
        if ($('.301-remove', $fieldSet301).length == 1) {
            $('.301-remove', $fieldSet301).addClass('translucide');
        }
    });

    $('.btn-changevisible').live('click', function(e) {
        e.preventDefault()
        var $this = $('.changevisible:checkbox', $(this).parents('.sort-elmt:first'));

        if ($this.is(':checked')) {
            $this.removeAttr('checked');
        } else {
            $this.attr('checked', 'checked');
        }

        if ($this.is(':checked')) {
            $this.next().val(1);
            $this.parent().first().next().removeClass('translucide');
            $(this).removeClass('btn-default').addClass('btn-success');
            $('i', this).removeClass('icon-eye-close').addClass('icon-eye-open');
        } else {
            $this.next().val(0);
            $this.parent().first().next().addClass('translucide');
            $(this).removeClass('btn-success').addClass('btn-default');
            $('i', this).removeClass('icon-eye-open').addClass('icon-eye-close');
        }
    });

    $('.js-checkbox').live('click', function() {
        if ($(this).is(':checked')) {
            $(this).next().val(1);
        } else {
            $(this).next().val(0);
        }
    });

    $('.delBloc').live('click', function(e) {
        e.preventDefault();

        if (!$(this).hasClass('translucide')) {
            sort_elmt = $(this).parents('.sort-elmt').first();
            sortpar = sort_elmt.parent();


            var heading = 'Confirmation de suppression d\'un bloc';
            var question = 'Etes-vous sur de vouloir supprimer ce bloc ? ';
            var cancelButtonTxt = 'Annuler';
            var okButtonTxt = 'Confirmer';

            var callback = function() {
                if (sort_elmt.find('textarea.tiny').length > 0)
                    sort_elmt.find('textarea.tiny').tinymce('disable');

                sort_elmt.slideUp('fast', function() {
                    if ($(this).siblings('.sort-elmt').length < 2) {
                        $(this).siblings('.sort-elmt').find('.delBloc').remove();
                        $(this).siblings('.sort-elmt').find('.sort-move').remove();
                    }
                    $(this).remove();
                    sortpar.sortable('refresh');
                });
                var heading = 'Confirmation de suppression d\'un bloc';
                var message = 'Le bloc'
                        + ' a été supprimé avec succès'
                var closeButtonTxt = 'Fermer';
                myModal.message(heading, message, closeButtonTxt, 2500);

            }

            myModal.confirm(heading, question, cancelButtonTxt, okButtonTxt, callback);
        }
    });

    $('.expand').live('click', function(e) {
        e.preventDefault();
        $(this).parent().nextAll('fieldset').each(function() {
            if ($('div', this).first().is(':hidden')) {
                $('legend', this).first().click();
            }
        });
    });

    $('.collapse').live('click', function(e) {
        e.preventDefault();
        $(this).parent().nextAll('fieldset').each(function() {
            if ($('div', this).first().is(':visible')) {
                $('legend', this).first().click();
            }
        });
    });

    $('.previsu').live('click', function(e) {
        e.preventDefault();
        image = $(this);

        var link = $(this).attr('href');
        var ext = link.split('.').pop().toLowerCase();
        if ($.inArray(ext, extensionsImage) != -1) {
            $('<img>', {
                'src': link
            }).load(function() {
                myModal.message('Prévisualisation', $(this), 'Fermer', false, true)
            });
        } else {

        }
    });

    var openingLegend = [];

    $('legend').live('click', function(e) {
        e.preventDefault();

        var indexLegend = $(this).index('legend');
        if (!openingLegend[indexLegend]) {
            openingLegend[indexLegend] = true;
            $(this).next().slideToggle(500, function() {
                $('.gmap-component', this).each(function() {
                    google.maps.event.trigger($(this).gmap3('get'), 'resize');
                    if ($('input.gmap_lat', $(this).parents('.line:first')).val() != '' && $('input.gmap_lng', $(this).parents('.line:first')).val() != '') {
                        var lat = $('input.gmap_lat', $(this).parents('.line:first')).val();
                        var lng = $('input.gmap_lng', $(this).parents('.line:first')).val();
                        $(this).gmap3('get').setCenter(new google.maps.LatLng(lat, lng))
                    }
                })
                openingLegend[indexLegend] = false;
                if ($(this).parent('.sort-elmt').parents('fieldset:first').find('.expand-collapse').length) {
                    disabledExpandCollaspse($(this).parent('.sort-elmt').parents('fieldset:first'));
                }
            });
        }
    });

    function MarkerControl(controlDiv, map, map3) {
        var chicago = new google.maps.LatLng(41.850033, -87.6500523);

        // Set CSS styles for the DIV containing the control
        // Setting padding to 5 px will offset the control
        // from the edge of the map.
        controlDiv.style.padding = '5px';

        // Set CSS for the control border.
        var controlUI = document.createElement('div');
        controlUI.style.backgroundColor = 'white';
        controlUI.style.borderStyle = 'solid';
        controlUI.style.borderWidth = '2px';
        controlUI.style.cursor = 'pointer';
        controlUI.style.textAlign = 'center';
        controlUI.className = 'gmap-marker-button';
        controlUI.title = 'Cliquez pour ajouter un marqueur au centre de la carte';
        controlDiv.appendChild(controlUI);

        // Set CSS for the control interior.
        var controlText = document.createElement('div');
        controlText.style.fontFamily = 'Arial,sans-serif';
        controlText.style.fontSize = '12px';
        controlText.style.paddingLeft = '4px';
        controlText.style.paddingRight = '4px';
        controlText.innerHTML = '<strong>Ajouter un marqueur</strong>';
        controlUI.appendChild(controlText);

        // Setup the click event listeners: simply set the map to Chicago.
        google.maps.event.addDomListener(controlUI, 'click', function() {
            var ctr = map.getCenter();
            var lat = ctr.lat();
            var lng = ctr.lng();

            if (map3.data('marker')) {
                removeMarker(map3)
            } else {
                addMarker(map3, lat, lng)
            }

        });
    }

    /**
     * Champ de type map
     */
    $('.gmap-component').livequery(function() {
        var $this = $(this)
        $(this).gmap3({
            map: {
                options: {
                    streetViewControl: false
                },
                events: {
                    zoom_changed: function(map) {
                        if ($this.data('marker')) {
                            $('input.gmap_zoom', $this.parents('.line:first')).val(map.getZoom());
                        }
                    }
                },
                callback: function(map) {
                    // Create the DIV to hold the control and call the HomeControl() constructor
                    // passing in this DIV.
                    var markerControlDiv = document.createElement('div');
                    $this.data('btn-marker', markerControlDiv)
                    var homeControl = new MarkerControl(markerControlDiv, map, $this);

                    markerControlDiv.index = 1;
                    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(markerControlDiv);

                    if ($('input.gmap_lat', $this.parents('.line:first')).val() != '' && $('input.gmap_lng', $this.parents('.line:first')).val() != '') {
                        var lat = $('input.gmap_lat', $this.parents('.line:first')).val();
                        var lng = $('input.gmap_lng', $this.parents('.line:first')).val();
                        var zoom = $('input.gmap_zoom', $this.parents('.line:first')).val();
                        map.setZoom(parseInt(zoom))
                        map.setCenter(new google.maps.LatLng(lat, lng))
                        addMarker($this, lat, lng)
                    }
                }
            },
        });
        var map = $(this).gmap3('get')


    });

    $('.form-date').datepicker($.datepicker.regional['fr']);

    function initAutocompletePat(elmt) {
        $('.form-file', elmt).each(function() {
            var tthis = $(this);

            tthis.autocomplete({
                source: function(request, response) {
                    var data = {
                        term: request.term,
                        id_gab_page: $('[name=id_gab_page]').val(),
                        id_temp: $('[name=id_temp]').val()
                    };

                    if (tthis.siblings('.extensions').length > 0)
                        data.extensions = tthis.siblings('.extensions').val();

                    $.getJSON(
                            'back/media/autocomplete.html',
                            data,
                            function(data, status, xhr) {
                                response(data);
                            }
                    );
                },
                minLength: 0,
                select: function(e, ui) {
                    e.preventDefault();

                    if ($(this).siblings('.previsu').length > 0)
                        $(this).siblings('.previsu').attr('href', ui.item.path);
                    $(this).val(ui.item.value);
                    $(this).siblings('.previsu').show();
                    var ext = ui.item.path.split('.').pop();
                    var isImage = $.inArray(ext, extensionsImage) != -1;
                    if (isImage) {
                        $(this).siblings('.crop').show();
                    } else {
                        $(this).siblings('.crop').hide();
                    }

                    $(this).autocomplete('close');

                    if (isImage) {
                        openCropDialog.call($(this).siblings('.crop'));
                    }
                }
            }).focus(function() {
                if (this.value == '') {
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        if (tthis.val() == '') {
                            tthis.autocomplete('search', '');
                        }
                    }, 220);
                }
            });

            tthis.data('autocomplete')._renderItem = function(ul, item) {
                var ext = item.value.split('.').pop();
                var prev = $.inArray(ext, extensionsImage) != -1
                        ? '<img class="img-polaroid" src="' + item.vignette + '" style="max-height:80px;width:auto;height:auto;max-width: 80px;" />'
                        : '<img style="width:auto" class="" src="img/back/filetype/' + ext + '.png" height="25" />';
                var inputs = [];
                $('.form-file').not(tthis).filter(function() {
                    return $(this).val() == item.value;
                }).each(function() {
                    inputs.push($(this).val());
                });


                /* Alert si image trop petite */
                var alert = '';
                if ($.inArray(ext, extensionsImage) != -1 && tthis.attr('data-min-width') && tthis.attr('data-min-width') > 0) {
                    var size = item.size.split('x');
                    if (parseInt(size[0]) < tthis.attr('data-min-width')) {
                        alert = '<dt style="color: red">Attention</dt><dd><span style="color: red">La largeur de l\'image est trop petite<span></dd>';
                    }
                }
                tthis.attr('data-min-width');
                return $('<li></li>')
                        .data('item.autocomplete', item)
                        .append('<a><span class="row">'
                        + (prev != '' ? '<span class="span1" style="margin-left:0px;">' + prev + '<span style="display:inline-block;width:120px"><i class="icon-info-sign"></i> ' + (inputs.length == 0 ? 'Non utilisé' : 'Utilisé') + '</span></span>' : '')
                        + '<span class="span" style="margin-left:0px;width:315px">'
                        + '<dl class="dl-horizontal"><dt>Nom de fichier</dt><dd><span>' + item.label + '<span></dd>' + (prev != "" ? '<dt>Taille</dt><dd><span>' + item.size + '<span></dd>' : '') + alert + '</dl>'
                        + '</span>'
                        + '</span></a>')
                        .appendTo(ul);
            };

            tthis.data('autocomplete')._renderMenu = function(ul, items) {
                var self = this;
                $.each(items, function(index, item) {
                    self._renderItem(ul, item);
                });
            };

            tthis.data('autocomplete').__response = function(content) {
                var contentlength = content.length;
                if (typeof uploader != 'undefined') {
                    contentlength += uploader.files.length;
                }

                if (!this.options.disabled
                        && content
                        && contentlength
                        ) {
                    content = this._normalize(content);
                    this._suggest(content);
                    this._trigger('open');
                } else {
                    this.close();
                }

                this.pending--;

                if (!this.pending) {
                    this.element.removeClass('ui-autocomplete-loading');
                }
            };

        });
    }

    initAutocompletePat('body');

    if ($('.langue').length > 1) {
        $('.openlang, .openlang-trad').click(function(e) {
            e.preventDefault();
            var $currentLang = $(this).parent().find('.openlang')

            var i = $('.openlang').index($currentLang);

            if ($('.langue').eq(i).is(':hidden')) {
                $('.openlang').addClass('translucide');
                $currentLang.removeClass('translucide')
                $('.langue:visible').slideUp(500);
                $('.langue').eq(i).slideDown(500);
            }
        });
    }

    //////////////////// PLUPLOAD ////////////////////
    basehref = $('base').attr('href');
    $.cookie('id_temp', 0, {
        path: '/'
    });

    uploader = new plupload.Uploader({
        runtimes: 'gears,html5,silverlight,flash,html4',
        browse_button: 'pickfiles',
        max_file_size: '1000mb',
        chunk_size: '2mb',
        url: basehref + 'back/media/upload.html?id_gab_page=' + $('[name=id_gab_page]').val(),
        flash_swf_url: basehref + 'js/admin/plupload/plupload.flash.swf',
        silverlight_xap_url: basehref + 'js/admin/plupload/plupload.silverlight.xap',
        filters: [
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
        drop_element: 'colright',
        unique_names: false,
        multiple_queues: true
    });

    uploader.bind('Init', function(up, params) {
        $('#currentruntime').text('Current runtime: ' + params.runtime);
    });

    var uploaderInited = false;

    var uploaderInit = function() {
        if (!uploaderInited) {
            uploaderInited = true;

            uploader.init();

            uploader.bind('FilesAdded', function(up, files) {
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
            });

            uploader.bind('UploadProgress', function(up, file) {
                $('.progress', file.tr).removeClass('hide')
                $('.bar', file.tr).css({
                    width: file.percent + '%'
                });
            });

            uploader.bind('Error', function(up, err) {
                err.file.error = true;
                up.refresh();
            });

            uploader.bind('FileUploaded', function(up, file, info) {

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

                uploader.splice(0, 1);
                rescale()

                if (uploader.files.length == 0) {
                    reloadDatatable();
                }
            });
        }
        else {
            uploader.refresh();
        }
    };

    $('#pickfiles').live('click', function(e) {
        e.preventDefault();
    });

    var $uploaderPopUp = $('<div>', {
        id: 'uploader_popup'
    }).load('back/media/popuplistefichiers.html?id_gab_page=' + $('[name=id_gab_page]').val(), function() {
        var heading = 'Importer des fichiers';
        var closeButtonTxt = 'Fermer';
        $('.uploader_popup').click(function(e) {
            e.preventDefault();
            myModal.message(heading, $uploaderPopUp, closeButtonTxt);
            $('#uploader_popup').parents('modal');
            if (oTable == null) {
                reloadDatatable();
            }
            uploaderInit();
        });
    });

    $('.rendrevisible').live('click', function() {
        var $this = $(this),
                id_gab_page = parseInt($this.parents('.sort-elmt').first().attr('id').split('_').pop()),
                checked = $this.is(':checked');

        $.post(
            'back/page/visible.html',
            {
                id_gab_page: id_gab_page,
                visible: checked ? 1 : 0
            },
            function(data) {
                if (data.status != 'success') {
                    $this.attr('checked', !checked);
                }
            },
            'json'
        );
    });

    /*
     * Message daide
     */
    $('form').each(function() {
        var formu = $(this);

        $('.form-controle:not([name="titre_rew"])', formu).livequery(function() {
            var id = $(this).attr('id').split('_');
            var name = id[0];
            var contentRule = [];
            var content = '<img style="float:left;" src="app/back/img/help.gif" alt="Aide" /><div style="margin-left:35px;margin-top:7px;">';
            if ($(this).hasClass('form-oblig')) {
                contentRule.push('<span style="color:red">Obligatoire</span>');
            } else {
                contentRule.push('<span style="color:#1292CC">Facultatif</span>');
            }

            var $this = $(this);
            if ($('#aide-' + name, formu).length != 0) {
                content += $('#aide-' + name, formu).html();
            } else {
                return false;
            }

            $this.attr('autocomplete', 'off').qtip({
                position: {
                    my: 'left center', // Position my top left...
                    at: 'center right' // at the bottom
                },
                content: {
                    text: content
                },
                style: {
                    classes: 'ui-tooltip-shadow ui-tooltip-bootstrap'
                }

            });
        });

        $('.mceEditor').live('mouseover', function() {
            var id = $(this).attr('id').split('_');
            var name = id[0];
            var contentRule = [];
            var content = '<img style="float:left;" src="app/back/img/help.gif" alt="Aide" /><div style="margin-left:35px;margin-top:7px;">';
            if ($(this).siblings('textarea').hasClass('form-oblig'))
                contentRule.push('<span style="color:red">Obligatoire</span>');
            else {
                contentRule.push('<span style="color:#1292CC">Facultatif</span>');
            }

            var $this = $(this);
            if ($('#aide-' + name, formu).length != 0) {
                content += $('#aide-' + name, formu).html();
            } else {
                return false;
            }

            $this.attr('autocomplete', 'off').qtip({
                position: {
                    my: 'left center', // Position my top left...
                    at: 'center right' // at the bottom
                },
                content: {
                    text: content
                },
                style: {
                    classes: 'ui-tooltip-shadow ui-tooltip-bootstrap'
                }

            });
            $this.qtip('show');
        });

        $('.mceEditor').live('mouseout', function() {
            var id = $(this).attr('id').split('_'),
                    name = id[0],
                    $this = $(this);

            $this.qtip('hide');
        });

    });

//    $('textarea').autogrow({
//        minHeight: 150
//    });
});

var oTable = null;

function disabledExpandCollaspse($fieldset) {
    var expand = false;
    var collapse = false;
    $fieldset.find('.sort-box > fieldset').each(function() {
        if ($(' > div:first', this).is(':visible')) {
            collapse = true;
        } else {
            expand = true;
        }
    });

    if (expand) {
        $fieldset.find(' > .sort-box > .expand-collapse .expand').removeClass('disabled');
    } else {
        $fieldset.find(' > .sort-box > .expand-collapse .expand').addClass('disabled');
    }

    if (collapse) {
        $fieldset.find(' > .sort-box > .expand-collapse .collapse').removeClass('disabled');
    } else {
        $fieldset.find(' > .sort-box > .expand-collapse .collapse').addClass('disabled');
    }
}

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

