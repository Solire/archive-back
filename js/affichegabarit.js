var extensionsImage = ['jpg', 'jpeg', 'gif', 'png'],
    sort_elmt = $(null),
    sortpar = $(null),
    basehref = '',
    delBtnClone,
    sortBtnClone,
    addBlocCallback = [],
    addBlocCalling = function(sortBox, clone) {
        $.each(addBlocCallback, function(ii, elmt){
            elmt(sortBox, clone);
        });
    };

tinymce.init({
    mode                : 'none',
    language            : 'fr_FR',
    height              :'290px',
    entity_encoding     : 'raw',
    plugins             : [
        'autolink link'
    ],
    menubar             : false,
    statusbar           : false,
    toolbar             : 'insertfile undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image',
    document_base_url   : '../../../../',
    image_list          : 'back/media/autocomplete.html?tinyMCE',
    link_list           : 'sitemap.xml?visible=0&json=1&onlylink=1&tinymce=1'
});

$(function() {
    delBtnClone = $('.delBloc:first'),
    sortBtnClone = $('.sort-move:first');
    addBlocCallback.push(function(sortBox, clone) {
        $('ul', clone).remove();
    });
    addBlocCallback.push(function(sortBox, clone) {
        $('legend', clone).html('Bloc en cours de création');
    });
    addBlocCallback.push(function(sortBox, clone) {
        sortBox.sortable('refresh');
    });
    addBlocCallback.push(function(sortBox, clone) {
        $('.sort-elmt .btn-bloc-action', sortBox).add($('.btn-bloc-action', clone)).each(function() {
            if ($('.sort-move', this).length == 0) {
                sortBtnClone.clone().prependTo(this);
            }

            if ($('.delBloc', this).length == 0) {
                delBtnClone.clone().appendTo(this);
            }
        });
    });
    addBlocCallback.push(function(sortBox, clone) {
        $('.form-date', clone).removeClass('hasDatepicker').val('').datepicker($.datepicker.regional['fr']);
        initAutocompletePat(clone);
        $('textarea.tiny', clone).tinymce('enable');
    });;

    $('.delBloc.to-remove').remove();
    $('.sort-move.to-remove').remove();

    $.cookie('id_gab_page', $('input[name=id_gab_page]').val(), {
        path: '/'
    });

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

    $('.addBloc').live('click', function(e) {
        e.preventDefault();

        var $this = $(this),
            adupliquer = $this.prev(),
            sortBox = $(this).parents('.sort-box'),
            clone;

        $('textarea.tiny', adupliquer).tinymce('disable');
        clone = adupliquer.clone(false).clearForm();
        clone.insertBefore($this);
        addBlocCalling(sortBox, clone);

        $('textarea.tiny', adupliquer).tinymce('enable');
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

            var heading = 'Confirmation de suppression d\'un bloc',
                question = 'Etes-vous sur de vouloir supprimer ce bloc ? ',
                cancelButtonTxt = 'Annuler',
                okButtonTxt = 'Confirmer';

            var callback = function() {
                var heading = 'Confirmation de suppression d\'un bloc',
                    message = 'Le bloc a été supprimé avec succès',
                    closeButtonTxt = 'Fermer';

                if (sort_elmt.find('textarea.tiny').length > 0)
                    sort_elmt.find('textarea.tiny').tinymce('disable');

                if ($('.sort-elmt', sortpar).length < 3) {
                    $('.delBloc', sortpar).hide();
                    $('.sort-move', sortpar).hide();
                }

                sort_elmt.slideUp('fast', function() {
                    $(this).remove();
                    sortpar.sortable('refresh');
                });

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

        var link = $(this).attr('href');
        var ext  = link.split('.').pop().toLowerCase();
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
                    var previsu = $(this).parent().siblings('.previsu');

                    e.preventDefault();

                    $(this).val(ui.item.value);
                    var ext = ui.item.path.split('.').pop();
                    var isImage = $.inArray(ext, extensionsImage) != -1;
                    if (isImage) {
                        $(this).siblings('.crop').show();
                    } else {
                        $(this).siblings('.crop').hide();
                    }

                    if (previsu.length > 0) {
                        previsu.attr('href', ui.item.path);
                        previsu.show();
                        $('.champ-image-value', previsu).text(ui.item.value);

                        if (isImage) {
                            $('.champ-image-size', previsu).text(ui.item.size).show();
                            $('.champ-image-size', previsu).prev().show();

                            $('.champ-image-vignette', previsu).attr('src', ui.item.vignette).show();
                        } else {
                            $('.champ-image-size', previsu).hide();
                            $('.champ-image-size', previsu).prev().hide();

                            $('.champ-image-vignette', previsu).hide();
                        }
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
});

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


