var extensionsImage = ['jpg', 'jpeg', 'gif', 'png'];
var sort_elmt = $(null);
var sortpar = $(null);
var basehref = '';

var initTinyMCE = function () {
    tinyMCE.init({
        mode: "none",
        theme : "advanced",
        //      valid_elements : "a[href],em/i,strike,u,strong/b,div[align],br,#p[align],-ol[type|compact],-ul[type|compact],-li",
        language : "fr",
        plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
        width:"500px",
        height:"290px",
        //      height:hauteur,

        // ne transforme plus les en html_entities
        entity_encoding : "raw",

        //      // Sauts de ligne en <br/>
        //      forced_root_block : false,
        //      force_br_newlines : true,
        //      force_p_newlines : false,

        // Theme options
        theme_advanced_buttons1 : "bold,italic,underline,strikethrough,styleselect,|,formatselect,|,bullist,numlist,|,undo,redo,|,link,unlink,image",
        theme_advanced_buttons2 : "",
        theme_advanced_buttons3 : "",

        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_resizing : true,

        theme_advanced_blockformats : "h1,h2,h3,h4,h5,h6",

        //        external_image_list_url : "../media/autocomplete.html?tinyMCE"


        relative_urls : true,
        //        remove_script_host : false,
        convert_urls : true,
        document_base_url : "../../../../",
        content_css : "back/css/style-tinymce.css",
        external_image_list_url : "back/media/autocomplete.html?tinyMCE",
        external_link_list_url : "back/page/autocomplete-link.html"
    });
}

initTinyMCE();

$(function(){
    $.cookie('id_gab_page', $('input[name=id_gab_page]').val(), {
        path : '/'
    });

    /**
     * Redimensionnement et recadrage des images
     */
    // Create variables (in this scope) to hold the API and image size
    var jcrop_api, boundx, boundy, $inputFile;



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

            updateCoords(c);
        }

    };

    function updateCoords(c)
    {
        $('#x').val(c.x);
        $('#y').val(c.y);
        $('#w').val(c.w);
        $('#h').val(c.h);
        $('.wShow').val(Math.round(c.w));
        $('.hShow').val(Math.round(c.h));
    };

    $('#modalCrop').modal({
        show : false,
        backdrop: true,
        keyboard: true
    }).addClass('modal-big');

    $('.wShow, .hShow').bind("change", function() {

        var w = parseInt($('.wShow').val());
        var h = parseInt($('.hShow').val());
        var x = parseInt($('#x').val());
        var y = parseInt($('#y').val());
        if(isNaN(x)) {
            x = 0;
        }

        if(isNaN(y)) {
            y = 0;
        }
        jcrop_api.setSelect([ x,y,x+w,y+h ]);
    });

    $('.spinner').spinner({
        min: 0
    });

    $(".form-crop-submit").bind("click", function() {
        var action = $(".form-crop").attr("action");
        var data = $(".form-crop").serialize();
        $.post(action, data, function(response) {
            $('#modalCrop').modal("hide");
            $inputFile.val(response.filename);
            $inputFile.parent().find(".previsu").attr("href", response.path);
        }, "json");
    });

    $(".crop").live("click", function(e) {
        var aspectRatio = 0;

        $(".img-info, .expected-width, .expected-height, .expected-width-height").hide();
        $(".force-selection input").attr("checked", "checked");

        e.preventDefault();
        $('.wShow').html("");
        $('.hShow').html("");

        var src = $(this).parent().prev().find('a').attr("href");

        $inputFile = $(this).parent().parent().find(".form-file");

        var $overlay = $('<div class="loading-overlay"><div class="circle"></div><div class="circle1"></div></div>').hide();
        $("body").prepend($overlay);
        var marginTop = Math.floor(($overlay.height() - $overlay.find(".circle").height()) / 2);
        $overlay.find(".circle").css({
            'margin-top' : marginTop + "px"
        });
        $overlay.fadeIn(500);

        $("<img>", {
            src: src
        }).load(function(){
            $('div.loading-overlay').remove();

            var minWidth = $inputFile.attr("data-min-width");
            var minHeight = $inputFile.attr("data-min-height");
            $('.spinner').spinner("destroy");
            $('.spinner.wShow').spinner({
                min: minWidth
            });
            $('.spinner.hShow').spinner({
                min: minHeight
            });
            if(parseInt(minWidth) > 0) {
                $("#minwidthShow").html(minWidth);
                $(".img-info, .expected-width").show();
                $(".expected-width").find("input").attr("checked", "checked");
            }

            if(parseInt(minHeight) > 0) {
                $("#minheightShow").html(minHeight);
                $(".img-info, .expected-height").show();
                $(".expected-height").find("input").attr("checked", "checked");
            }

            if(parseInt(minHeight) > 0 && parseInt(minWidth) > 0) {
                $("#minheightShow").html(minHeight);
                $(".expected-width-height").show();
                $(".expected-width-height").find("input").attr("checked", "checked");
                $("label.expected-width").hide();
                $("label.expected-height").hide();
                aspectRatio = minWidth / minHeight;
            }

            $("#minwidth").val(minWidth);
            $("#minheight").val(minHeight);
            var imageNameInfos = $inputFile.val().split('.');
            var imageExtension = imageNameInfos.pop();
            var imageName = imageNameInfos.join("");

            $("#image-name").val(imageName);
            $("#image-extension").val(imageExtension);
            $("#modalCrop table tr:first td:first ").html('<img src="" class="img-polaroid" id="crop-target" alt="" />');
            $("#modalCrop #filepath").val(src);
            $("#crop-target").add("#crop-preview").attr("src", src);
            $(".jcrop-holder").remove();
            $('#modalCrop').modal("show");
            $('#crop-target').Jcrop({
                minSize : [minWidth, minHeight],
                boxWidth: 540,
                boxHeight: 400,
                onChange: updatePreview,
                onSelect: updatePreview,
                aspectRatio: aspectRatio
            },function(){
                // Use the API to get the real image size
                var bounds = this.getBounds();
                boundx = bounds[0];
                boundy = bounds[1];
                // Store the API in the jcrop_api variable
                jcrop_api = this;
            });
        });

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
        monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin',
        'Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
        monthNamesShort: ['Janv.','Févr.','Mars','Avril','Mai','Juin',
        'Juil.','Août','Sept.','Oct.','Nov.','Déc.'],
        dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
        dayNamesShort: ['Dim.','Lun.','Mar.','Mer.','Jeu.','Ven.','Sam.'],
        dayNamesMin: ['D','L','M','M','J','V','S'],
        weekHeader: 'Sem.',
        dateFormat: 'dd/mm/yy',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: ''
    };

    $.fn.clearForm = function(){
        var idnew;
        this.find(".token-input-list").remove();
        this.find('input, textarea, select').not('[name="visible[]"]').not(".join-param").not(".extensions").each(function(){
            idnew = $(this).attr('id')+'a';
            $(this).attr('id', idnew);
            $(this).prev('label').attr('for', idnew);

            if($(this).is('input'))
                $(this).val('');
            else{
                if($(this).is('textarea')){
                    $(this).tinymce('disableOnly');
                    $(this).val('');
                }
                else{
                    if($(this).is('select'))
                        $(this).val($(this).children('option:first').val());
                }
            }
        });

        this.find('.previsu').attr('href', '');
        this.find('.previsu').parent().hide();
        this.find('.crop').parent().hide();

        return this;
    };

    var tinyMethods = {
        disable : function(){
            //var base = this;
            $('#tempId').attr('id','');
            if (this.$el.attr('id') == '') {
                this.$el.attr('id', 'tempId');
            }
            var tinyId = this.$el.attr('id');

            tinyMCE.execCommand('mceFocus', false, tinyId);
            tinyMCE.execCommand('mceRemoveControl', false, tinyId);
            tinyMCE.triggerSave(true, true);
        },
        enable : function(){
            //var base = this;
            $('#tempId').attr('id','');
            if(this.$el.attr('id')=='') {
                this.$el.attr('id', 'tempId');
            }
            var tinyId = this.$el.attr('id');
            tinyMCE.execCommand('mceAddControl',false,tinyId);
        },
        change : function(){
            $('#tempId').attr('id','');
            if(this.$el.attr('id')=='') {
                this.$el.attr('id', 'tempId');
            }
            var tinyId = this.$el.attr('id');

            if(tinyMCE.getInstanceById(tinyId))
                tinyMethods['disable'].apply(this);
            else
                tinyMethods['enable'].apply(this);

        //			tinyMCE.execCommand('mceToggleEditor',false,tinyId);
        },
        disableOnly : function(){
            $('#tempId').attr('id','');
            if(this.$el.attr('id')=='') {
                this.$el.attr('id', 'tempId');
            }
            var tinyId = this.$el.attr('id');

            if(tinyMCE.getInstanceById(tinyId)){
                tinyMethods['disable'].apply(this);
                this.$el.addClass('tinymce-tmp-disabled');
            }
        },
        enableOnly : function(){
            $('#tempId').attr('id','');
            if(this.$el.attr('id')=='') {
                this.$el.attr('id', 'tempId');
            }
            var tinyId = this.$el.attr('id');

            if(!tinyMCE.getInstanceById(tinyId))
                tinyMethods['enable'].apply(this);

            this.$el.removeClass('tinymce-tmp-disabled');
        }
    };

    $.tinymce = function(method, el){
        var base=this;
        base.$el = $(el);
        base.el = el;

        return tinyMethods[method].apply(this);
    };

    $.fn.tinymce = function(method){
        var tab = [];

        this.each(function(){
            tab.push(new $.tinymce(method, this));
        });
        return tab;
    };

    $('textarea.tiny').tinymce('enable');

    $('label > .switch-editor').live('click', function(e){
        e.preventDefault();

        if($(this).parent().next().is('textarea')){
            if($(this).children().eq(0).hasClass('translucide')) {
                $(this).children().eq(0).removeClass('translucide');
                $(this).children().eq(1).addClass('translucide');
            }
            else{
                $(this).children().eq(0).addClass('translucide');
                $(this).children().eq(1).removeClass('translucide');
            }

            $(this).parent().next().tinymce('change');
        }
    });

    //// GESTION DU TRI
    $('.sort-box').each(function(){
        $(this).sortable({
            placeholder: 'empty',
            items: '.sort-elmt',
            handle: '.sort-move',
            deactivate: function() {
            //callback();
            },
            start: function(e, ui){
                $('textarea', ui.item).tinymce('disableOnly');
            },
            stop: function(e, ui){
                $('textarea.tinymce-tmp-disabled', ui.item).tinymce('enableOnly');
            }
        });
    });
    $('.addBloc').live('click', function(e){
        e.preventDefault();

        var $this = $(this).parents('.buttonright').first();
        var adupliquer = $this.prev();
        $('textarea.tiny', adupliquer).tinymce('disableOnly');
        var clone = adupliquer.clone(false).clearForm();
        clone.find("ul").remove();
        clone.insertBefore($this);
        clone.find("legend").html("Nouvel élément");
        $this.parents('.sort-box').sortable('refresh');
        $this.siblings('.sort-elmt').find('.delBloc').removeClass('translucide');
        $this.find('.form-date').datepicker($.datepicker.regional['fr']);

        initAutocompletePat();
        $('textarea', clone).autogrow({
            minHeight :   150
        });
        $('textarea.tiny', adupliquer).tinymce('enableOnly');
        $('textarea.tiny', clone).tinymce('enableOnly');
    });

    $('.301-add').live('click', function(e){
        e.preventDefault();

        var $this = $(this).parents('fieldset:first').find('.line:first');
        var $fieldSet301 = $(this).parents("fieldset:first");
        var adupliquer = $this;
        var clone = adupliquer.clone(false).clearForm();
        $(".301-remove", clone).removeClass("translucide");
        clone.insertAfter($(this).parents('fieldset:first').find('.line:last'));
        if($(".301-remove", $fieldSet301).length > 1) {
            $(".301-remove", $fieldSet301).removeClass("translucide");
        }
    });

    $('.301-remove:not(.translucide)').live('click', function(e){
        e.preventDefault();

        var $this = $(this).parents('.line:first');
        var $fieldSet301 = $(this).parents("fieldset:first");
        $this.remove();
        if ($(".301-remove", $fieldSet301).length == 1) {
            $(".301-remove", $fieldSet301).addClass("translucide");
        }
    });

    var confirm = $('<div>', {
        id : 'confirm'
    }).dialog({
        title : "Attention",
        resizable : false,
        open: function(){
            $('.ui-widget-overlay').hide().fadeIn();
            if(!$('.ui-dialog-buttonset button').hasClass("btn-a"))
                $('.ui-dialog-buttonset button').attr("class", "").addClass("btn-a gradient-blue").unbind('mouseout keyup mouseup hover mouseenter mouseover focusin focusout mousedown focus').wrapInner("<a></a>");
        },
        beforeClose: function(){
            $('.ui-widget-overlay').remove();
            $("<div />", {
                'class':'ui-widget-overlay'
            }).css({
                height: $(document).height(),
                width: $(document).width(),
                zIndex: 1001
            }).appendTo("body").fadeOut(function(){
                $(this).remove();
            });
        },
        buttons: {
            "Ok" : function(){
                if(sort_elmt.find('textarea.tiny').length > 0)
                    sort_elmt.find('textarea.tiny').tinymce('disableOnly');

                sort_elmt.slideUp('fast', function(){
                    if ($(this).siblings('.sort-elmt').length < 2)
                        $(this).siblings('.sort-elmt').find('.delBloc').addClass('translucide');
                    $(this).remove();
                    sortpar.sortable('refresh');
                });

                $(this).dialog("close");
            },
            "Annuler" : function(){
                $(this).dialog("close");
            }
        },
        autoOpen : false,
        modal: true,
        close: function(event, ui){
            sort_elmt = $(null);
        }
    });

    var previsu = $('<div>', {
        id: 'previsu'
    }).dialog({
        title : "Prévisualisation",

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


    ///////////////////////////////
    //// GESTION DES EVENMENTS ////

    $('.changevisible').live('click', function(){
        if($(this).is(':checked')){
            $(this).next().val(1);
            $(this).parent().first().next().removeClass('translucide');
        }
        else{
            $(this).next().val(0);
            $(this).parent().first().next().addClass('translucide');
        }
    });

    $('.js-checkbox').live('click', function(){
        if($(this).is(':checked')){
            $(this).next().val(1);
        } else {
            $(this).next().val(0);
        }
    });

    $('.delBloc').live('click', function(e){
        e.preventDefault();

        if (!$(this).hasClass('translucide')) {
            sort_elmt = $(this).parents('.sort-elmt').first();
            sortpar = sort_elmt.parent();

            confirm.html("Etes-vous sur de vouloir supprimer ce bloc?");
            confirm.dialog('open');
            $(".delBloc", sort_elmt).effect("transfer", {
                to: confirm.dialog("widget"),
                className: "ui-effects-transfer"
            }, 500);
        }
    });


    $(".expand").live("click", function(e) {
        e.preventDefault();
        $(this).parent().nextAll("fieldset").each(function() {
            if($(this).find("div:first").is(":hidden")) {
                $(this).find("legend:first").click();
            }
        });
    });

    $(".collapse").live("click", function(e) {
        e.preventDefault();
        $(this).parent().nextAll("fieldset").each(function() {
            if($(this).find("div:first").is(":visible")) {
                $(this).find("legend:first").click();
            }
        });
    });


    $('.previsu').live('click', function(e){
        e.preventDefault();
        image = $(this);

        var link = $(this).attr('href');
        var ext = link.split('.').pop().toLowerCase();
        if ($.inArray(ext, extensionsImage) != -1) {
            $('<img>', {
                'src' : link
            }).load(function(){
                previsu.dialog( "option" , "height" , "auto" );
                previsu.dialog( "option" , "maxWidth" , $(window).width()-180 );
                previsu.dialog( "option" , "maxHeight" , $(window).height()-230 );
                previsu.dialog('close');
                previsu.dialog('open');
                previsu.dialog('option', 'position', "center");
                previsu.html(this);
            });
        } else {
            previsu.dialog( "option" , "height" , 0 );
            previsu.html('');
            previsu.dialog('close');
            previsu.dialog('open');
            previsu.dialog('option', 'position', "center");
        }
    });

    var openingLegend = [];

    $('legend').bind('click', function(e){
        e.preventDefault();

        var indexLegend = $(this).index("legend");
        if (!openingLegend[indexLegend]) {
            openingLegend[indexLegend] = true;
            $(this).next().slideToggle(500, function() {
                openingLegend[indexLegend] = false;
                if ($(this).parent(".sort-elmt").parents("fieldset:first").find(".expand-collapse").length) {
                    disabledExpandCollaspse($(this).parent(".sort-elmt").parents("fieldset:first"));
                }
            });
        }
    });

    $('.form-date').datepicker($.datepicker.regional['fr']);

    function initAutocompletePat(){
        $('.form-file').each(function(){
            var tthis = $(this);

            tthis.autocomplete({
                source: function(request, response) {
                    var data = {
                        term        : request.term,
                        id_gab_page : $('[name=id_gab_page]').val(),
                        id_temp     : $('[name=id_temp]').val()
                    };

                    if (tthis.siblings('.extensions').length > 0)
                        data.extensions = tthis.siblings('.extensions').val();

                    $.getJSON(
                        'back/media/autocomplete.html',
                        data,
                        function( data, status, xhr ) {
                            response( data );
                        }
                        );
                },
                minLength: 0,
                select: function(e, ui) {
                    e.preventDefault();

                    if($(this).siblings(".btn-a").find('.previsu').length > 0)
                        $(this).siblings(".btn-a").find('.previsu').attr('href', ui.item.path);
                    $(this).val(ui.item.value);
                    $(this).siblings(".btn-a").find('.previsu').parent().show();
                    var ext     = ui.item.path.split('.').pop();
                    var isImage = $.inArray(ext, extensionsImage) != -1;
                    if(isImage) {
                        $(this).siblings(".btn-a").find('.crop').parent().show();
                    } else {
                        $(this).siblings(".btn-a").find('.crop').parent().hide();
                    }

                    $(this).autocomplete("close");

                }
            }).focus(function(){
                if (this.value == "") {
                    clearTimeout(timer);
                    timer = setTimeout(function(){
                        if (tthis.val() == "") {
                            tthis.autocomplete('search', '');
                        }
                    },220);
                }
            });

            tthis.data("autocomplete")._renderItem = function(ul, item){
                var ext     = item.value.split('.').pop();
                var prev    = $.inArray(ext, extensionsImage) != -1
                            ? '<img class="img-polaroid" src="'+item.vignette+'" style="max-height:80px;width:auto;height:auto;max-width: 80px;" />'
                            : '<img style="width:auto" class="" src="img/back/filetype/'+ext+'.png" height="25" />';
                /* Alert si image trop petite */
                var alert = "";
                if($.inArray(ext, extensionsImage) != -1 && tthis.attr("data-min-width") && tthis.attr("data-min-width") > 0) {
                    var size = item.size.split("x");
                    if (parseInt(size[0]) < tthis.attr("data-min-width")) {
                        alert = '<dt style="color: red">Attention</dt><dd><span style="color: red">La largeur de l\'image est trop petite<span></dd>';
                    }
                }
                tthis.attr("data-min-width");
                return $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append(  '<a><span class="row">'
                    + (prev != '' ?  '<span class="span1" style="margin-left:0px;">' + prev + '</span>': '' )
                    + '<span class="span" style="margin-left:0px;width:315px">'
                    + '<dl class="dl-horizontal"><dt>Nom de fichier</dt><dd><span>'+item.label+'<span></dd>' + (prev != "" ? '<dt>Taille</dt><dd><span>'+item.size+'<span></dd>' : '' ) + alert + '</dl>'
                    + '</span>'
                    + '</span></a>')
                .appendTo( ul );
            };

            tthis.data("autocomplete")._renderMenu = function( ul, items ) {
                var self = this;
                $.each( items, function( index, item ) {
                    self._renderItem( ul, item );
                });
            };

            tthis.data("autocomplete").__response = function( content ) {
                var contentlength = content.length;
                if (typeof uploader != "undefined") {
                    contentlength += uploader.files.length;
                }

                if (!this.options.disabled
                    && content
                    && contentlength
                ) {
                    content = this._normalize( content );
                    this._suggest(content);
                    this._trigger("open");
                } else {
                    this.close();
                }

                this.pending--;

                if (!this.pending) {
                    this.element.removeClass( "ui-autocomplete-loading" );
                }
            };

        });
    }

    initAutocompletePat();

    if ($('.langue').length > 1) {
        $('.openlang').click(function(e) {
            e.preventDefault();

            var i = $('.openlang').index($(this));

            if($('.langue').eq(i).is(':hidden')) {
                $('.openlang').removeClass('active').addClass('translucide');
                $(this).removeClass('translucide').addClass('active');
                $('.langue:visible').slideUp(500);
                $('.langue').eq(i).slideDown(500);
            }
        });
    }

    //////////////////// PLUPLOAD ////////////////////
    basehref = $('base').attr('href');
    $.cookie("id_temp", 0, {
        path : '/'
    });

    uploader = new plupload.Uploader({
        runtimes : 'gears,html5,silverlight,flash,html4',
        browse_button : 'pickfiles',
        max_file_size : '1000mb',
        chunk_size : '2mb',
        url : basehref + 'back/media/upload.html?id_gab_page=' + $('[name=id_gab_page]').val(),
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

    var uploaderInited = false;

    var uploaderInit = function(){
        if (!uploaderInited) {
            uploaderInited = true;

            uploader.init();

            uploader.bind('FilesAdded', function(up, files) {
                $.each(files, function(i, file) {
                    var tr, td;
                    if(!file.error) {
                        tr = $('<tr>');
                        $('<td>', {
                            colspan : 4
                        }).html(file.name + '<div class="progressbar"></div>').appendTo(tr);
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
                            file.tr.insertAfter(files[i-1].tr);
                        }
                    }
                });

                $('.progressbar').progressbar({
                    value: 0
                });

                up.refresh();
                up.start();
            });

            uploader.bind('UploadProgress', function(up, file) {
                $('.progressbar', file.tr).progressbar("value", file.percent);
            });

            uploader.bind('Error', function(up, err) {
                err.file.error = true;
                up.refresh();
            });

            uploader.bind('FileUploaded', function(up, file, info) {

                $(file.tr, '.progressbar').progressbar("destroy");

                var response = $.parseJSON(info.response);

                if(response.status != "error") {
                    if ('id_temp' in response) {
                        $('input[name=id_temp]:first').val(response.id_temp);
                        $.cookie("id_temp", response.id_temp, {
                            path : '/'
                        });
                    }

                    $('.atelecharger-' + file.id).val(response.filename);

                    var ligne = '';

                    ligne += '<td><a href="' + response.path + '" id="fileid_' + response.id + '" target="_blank" class="previsu">';

                    var ext = file.name.split('.').pop().toLowerCase();
                    if ($.inArray(ext, extensionsImage) != -1) {
                        ligne += '<img class="vignette img-polaroid" src="' + response.minipath + '" alt="' + ext + '" /></a></td>';
                    } else {
                        ligne += '<img class="vignette" src="img/back/filetype/' + ext + '.png" alt="' + ext + '" /></a></td>';
                    }

                    ligne += '<td>' + response.size + '</td>';
                    ligne += '<td>' + response.date.substr(0, 10) + '<br />' + response.date.substr(11) + '</td>';
                    ligne += '<td><div class="btn-a gradient-blue"><a target="_blank" href="' + response.path + '" class="previsu"><img alt="Voir" src="img/back/voir.png" /></a></a></td>';

                    file.tr.attr("id", "fileid_" + response.id);
                    file.tr.html(ligne);
                }
                else {
                    file.tr.remove();
                }

                uploader.splice(0, 1);

                if (uploader.files.length == 0) {
                    reloadDatatable();
                }
            });
        }
        else {
            uploader.refresh();
        }
    };

    $('#pickfiles').live('click', function(e){
        e.preventDefault();
    });

    var uploader_popup = $('<div>', {
        id : 'uploader_popup'
    }).load('back/media/popuplistefichiers.html?id_gab_page=' + $('[name=id_gab_page]').val(), function(){
        $(this).dialog({
            title : "Fichiers",
            autoOpen : false,
            width : 625,
            resizable : false,
            height: "auto",
            maxHeight : $(window).height()-230,
            maxWidth : $(window).width()-180
        }).css({
            "max-height" : $(window).height()-230,
            "max-width" : $(window).width()-180
        });

        $('.uploader_popup').click(function(e){
            e.preventDefault();

            if(oTable == null) {
                reloadDatatable();
            }

            uploader_popup.dialog("open");
            uploaderInit();
        });
    });

    $('.rendrevisible').live('click', function(){
        var $this = $(this),
            id_gab_page = parseInt($this.parents('.sort-elmt').first().attr('id').split('_').pop()),
            checked = $this.is(':checked');

        $.post(
            'back/page/visible.html',
            {
                id_gab_page : id_gab_page,
                visible     : checked ? 1 : 0
            },
            function(data){
                if (data.status != 'success') {
                    $this.attr('checked', !checked);
                }
            },
            'json'
        );
    });

//    if ($('form').length > 1) {
//
//        $('form').not('form:first').each(function(){
//            var formu = $(this);
//            $('.form-controle:not([name="titre_rew"])', formu).attr('autocomplete','off').tipsy({
//                trigger: 'focus',
//                gravity: 'n',
//                opacity: 1,
//                html: true,
//                title: function(){
//                    var name = $(this).attr("name");
//                    var eq = $('.form-controle[name="' + name + '"]', formu).index($(this));
//                    return $('form:first .form-controle[name="' + name + '"]').eq(eq).val();
//                }
//            });
//        });
//    }

    /*
     * Message daide
     */
    $('form').each(function(){

        var formu = $(this);


        $('.form-controle:not([name="titre_rew"])', formu).livequery(function() {
            var id = $(this).attr("id").split("_");
            var name = id[0];
            var contentRule = [];
            var content = '<img style="float:left;" src="img/back/help.gif" alt="Aide" /><div style="margin-left:35px;margin-top:7px;">';
            if($(this).hasClass("form-oblig"))
                contentRule.push('<span style="color:red">Obligatoire</span>');
            else {
                contentRule.push('<span style="color:#1292CC">Facultatif</span>');
            }

            var $this = $(this);
            if($('#aide-' + name, formu).length != 0) {
                content += $('#aide-' + name, formu).html();
            } else {
                return false;
            }

            $this.attr('autocomplete','off').qtip({
                position: {
                    my: 'left center',  // Position my top left...
                    at: 'center right' // at the bottom
                },
                content: {
                    text:  content
                },
                style: {
                    classes: 'ui-tooltip-shadow ui-tooltip-bootstrap'
                }

            });
        });

        $(".mceEditor").live("mouseover", function() {
            var id = $(this).attr("id").split("_");
            var name = id[0];
            var contentRule = [];
            var content = '<img style="float:left;" src="img/back/help.gif" alt="Aide" /><div style="margin-left:35px;margin-top:7px;">';
            if($(this).siblings('textarea').hasClass("form-oblig"))
                contentRule.push('<span style="color:red">Obligatoire</span>');
            else {
                contentRule.push('<span style="color:#1292CC">Facultatif</span>');
            }

            var $this = $(this);
            if($('#aide-' + name, formu).length != 0) {
                content += $('#aide-' + name, formu).html();
            } else {
                return false;
            }

            $this.attr('autocomplete','off').qtip({

                position: {
                    my: 'left center',  // Position my top left...
                    at: 'center right' // at the bottom
                },
                content: {
                    text:  content
                },
                style: {
                    classes: 'ui-tooltip-shadow ui-tooltip-bootstrap'
                }

            });
            $this.qtip('show');
        });

        $(".mceEditor").live("mouseout", function() {
            var id = $(this).attr("id").split("_"),
                name = id[0],
                $this = $(this);

            $this.qtip('hide');
        });

    });

    $('textarea').autogrow({
        minHeight :   150
    });
});

var oTable = null;

function disabledExpandCollaspse($fieldset) {
    var expand = false;
    var collapse = false;
    $fieldset.find(".sort-box > fieldset").each(function() {
        if ($(" > div:first", this).is(":visible")) {
            collapse = true;
        } else {
            expand = true;
        }
    });

    if (expand) {
        $fieldset.find(" > .sort-box > .expand-collapse .expand").removeClass("disabled");
    } else {
        $fieldset.find(" > .sort-box > .expand-collapse .expand").addClass("disabled");
    }

    if (collapse) {
        $fieldset.find(" > .sort-box > .expand-collapse .collapse").removeClass("disabled");
    } else {
        $fieldset.find(" > .sort-box > .expand-collapse .collapse").addClass("disabled");
    }
}

function reloadDatatable() {
    if(oTable != null) {
        oTable.fnDestroy();
    }

    $("#tableau").css({
        width : "100%"
    });

    oTable = $("#tableau").dataTable({
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
            sProcessing   : "Chargement...",
            sLengthMenu   : "Montrer _MENU_ fichiers par page",
            sZeroRecords  : "Aucun fichier trouvé",
            sEmptyTable   : "Pas de fichier",
            sInfo         : "fichiers _START_ à  _END_ sur _TOTAL_ fichiers",
            sInfoEmpty    : "Aucun fichier",
            sInfoFiltered : "(filtre sur _MAX_ fichiers)",
            sInfoPostFix  : "",
            sSearch       : "",
            sUrl          : "",
            oPaginate: {
                sFirst    : "",
                sPrevious : "",
                sNext     : "",
                sLast     : ""
            }
        }
    });

    $('.dataTables_filter input').attr("placeholder", "Recherche...");
}

