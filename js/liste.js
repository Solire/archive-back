//var sort_elmt = $(null);
//var sort_box = $(null);
var positions = {};

$(function(){
    var confirmationSupprMessage = '<p style="color: red;">'
    confirmationSupprMessage += '<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Attention ! Cette action supprimera la page dans tous les langues.'
    confirmationSupprMessage += '</p>'
    confirmationSupprMessage += '<div style="margin-left: 23px;margin-top: 16px;">Etes-vous sur de vouloir supprimer cette page ?</div>'

    //// SUPPRIMER UNE PAGE.
    confirm = $('<div>')
    .html(confirmationSupprMessage)
    .dialog({
        open: function(){
            $('.ui-widget-overlay').hide().fadeIn();
            if(!$('.ui-dialog-buttonset button').hasClass("btn")) {
                $('.ui-dialog-buttonset button:eq(0)').attr("class", "").addClass("btn btn-warning btn-small").unbind('mouseout keyup mouseup hover mouseenter mouseover focusin focusout mousedown focus').wrapInner("<a></a>");
                $('.ui-dialog-buttonset button:eq(1)').attr("class", "").addClass("btn btn-default btn-small").unbind('mouseout keyup mouseup hover mouseenter mouseover focusin focusout mousedown focus').wrapInner("<a></a>");
            }
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

        modal: true,
        width : "446px",
        autoOpen : false,
        resizable: false,
        title : "Confirmation de suppression de page",
        show: {
            effect:   "fade",
            duration: 1000
        },
        hide: {
            effect:   "fade",
            duration: 500
        },

        buttons: {
            "Ok" : function(){
                $(this).dialog("close");
            },
            "Annuler" : function(){
                $(this).dialog("close");
            }
        }
    });

    var confirmOpen = function(sort_elmt) {
        var sort_box = sort_elmt.parent();
        var id_gab_page = parseInt(sort_elmt.attr('id').split('_').pop());
        var titleElemDel = sort_elmt.attr("data-titre")
        var heading = 'Confirmation de suppression de "' + titleElemDel + '"';
        var question = 'Etes-vous sûr de vouloir supprimer "' + titleElemDel + '" ? ';
        var cancelButtonTxt = 'Annuler';
        var okButtonTxt = 'Confirmer';

        var callback = function() {
            $.post(
                'back/page/delete.html',
                {
                    id_gab_page : id_gab_page
                },
                function(data){
                    if(data.status == 'success')
                        sort_elmt.slideUp('fast', function(){
                            $(this).remove();
                            sort_box.sortable('refresh');
                            var heading = 'Confirmation de suppression de "' + titleElemDel + '"';
                            var message = '"' + titleElemDel + '"'
                                + ' a été supprimé avec succès'
                            var closeButtonTxt = 'Fermer';
                            myModal.message(heading, message, closeButtonTxt, 2500);
                        })
                },
                'json'
                );
            };

            myModal.confirm(heading, question, cancelButtonTxt, okButtonTxt, callback);


    }

    $('.supprimer').live('click', function(e){
        e.preventDefault();
        confirmOpen($(this).parents('.sort-elmt').first());
    });

    //// RENDRE VISIBLE UNE PAGE.
    $('.rendrevisible').live('click', function(){
        var $this = $(this);
        var id_gab_page = parseInt($this.parents('.sort-elmt').first().attr('id').split('_').pop());
        var checked = $this.is(':checked');

        $.post(
            'back/page/visible.html',
            {
                id_gab_page : id_gab_page,
                visible     : checked ? 1 : 0
            },
            function(data){
                if(data.status != 'success') {
                    $this.attr('checked', !checked);
                    $.sticky("Une erreur est survenue", {
                        type:"error"
                    });
                } else {
                    if(checked) {
                        $.sticky("La page a été rendue visible", {
                            type:"success"
                        });
                    } else {
                        $.sticky("La page a été rendue invisible", {
                            type:"success"
                        });
                    }
                }
            },
            'json'
        );
    });

    //// GESTION DU TRI DES PAGES.
    var initTri = function () {
        $('.sort-box').each(function(){
            var i = 1;
            $(this).children().each(function(){
                positions[parseInt($(this).attr('id').split('_').pop())] = i++;
            });

            $(this).sortable({
                placeholder: 'empty',
                items: '> .sort-elmt',
                handle: '.sort-move',
                update: function(){
                    var i = 1;
                    $(this).children().each(function(){
                        positions[parseInt($(this).attr('id').split('_').pop())] = i++;
                    });
                    orderProcess();
                }
            });
        });
    }

    var orderProcess = function(){
        $.post('back/page/order.html', {
            'positions' : positions
        }, function(data){
            if(data == "Succès") {
                $.sticky("Succès du déplacement", {
                    type:"success"
                });
            } else {
                $.sticky("Une erreur est survenue.", {
                    type:"error"
                });
            }

        });

        return false;
    }

    initTri();


    $('select[name=id_sous_rubrique]').change(function(){
        var id_sous_rubrique = $(this).val();
        $.cookie('id_sous_rubrique', id_sous_rubrique, {
            path : '/'
        });
        $(this).parents('form').submit();
    });

    //// OUVERTURE / FERMETURE DES PAGES PARENTES.
    $('legend').live('click', function(){
        var $legend = $(this)
        if ($legend.hasClass("noChild")) {
            return false;
        }
        if ($(this).next('div').is(':hidden') && $(this).next('div').html()=='') {

            $legend.find('i.icon-chevron-down').addClass("icon-chevron-up").removeClass("icon-chevron-down")
            if (!$(this).next('div').hasClass('children-loaded')) {
                var id = $(this).parent().attr('id').split('_').pop();

                var $divToLoad = $(this).next('div')
                $.ajax({
                    mode: 'queue',
                    port: 'ajaxWhois',
                    type: 'GET',
                    url: 'back/page/children.html',
                    data: {
                        id_parent : id
                    },
                    success: function(data){
//                        console.log(id)
                        $divToLoad.html(data)
                        $divToLoad.addClass('children-loaded');
                        if (data != '') {
                            initTri();
                            $divToLoad.slideToggle(500);
                            $divToLoad.siblings('.cat-modif').slideToggle(500);
                        }
                    }
                });
                $.ajax({
                    mode: 'dequeue',
                    port: 'ajaxWhois'
                });
            }
        }
        else {
            $legend.find('i.icon-chevron-down, i.icon-chevron-up').toggleClass("icon-chevron-up").toggleClass("icon-chevron-down")
            $(this).next('div').slideToggle(500);
            $(this).siblings('.cat-modif').slideToggle(500);
        }

        saveState()

        return false;
    });





    /**
     * Enregistre l'état de la liste des pages (Rubriques dépliées)
     * Celui-ci est stocké en cookie
     */
    function saveState() {
        var saveStateListPage = []
        $("legend i.icon-chevron-up").each(function(){
            saveStateListPage.push($(this).parents("fieldset:first").attr("id"))
        })

        $.cookie('state_list', saveStateListPage, {
            path : '/'
        });
    }

    var anchorFound = false;
    var currentState = 0

    /**
     * Recharge l'état de la liste des pages (Rubriques dépliées)
     * Récupéré dans le cookie correspondant
     */
    function reloadState() {
        var saveStateListPage;

        if ($.cookie('state_list')) {
            saveStateListPage = $.cookie('state_list').split(",");
        }
        else {
            saveStateListPage = [];
        }

        $.each(saveStateListPage, function(id, item) {

            var id = item.split('_').pop();
            $.ajax({
                mode: 'queue',
                port: 'ajaxWhois',
                type: 'GET',
                url: 'back/page/children.html',
                data: {
                    id_parent : id
                },
                success: function(data){
                    currentState++;
                    var $legend = $("#" + item).find("legend:first")
                    var $divToLoad = $("#" + item).find(".sort-box:first")
                    $legend.find('i.icon-chevron-down').toggleClass("icon-chevron-up").toggleClass("icon-chevron-down")
                    $divToLoad.html(data)
                    $divToLoad.addClass('children-loaded');
                    if (data != '') {
                        initTri();
                        $divToLoad.slideToggle(500, function() {
                            if (currentState == saveStateListPage.length
                                && anchorFound !== false) {
                                var heightFixed = 0
                                //Si la navbar est en fixed (taille écran > 980px)
                                if($(".navbar-fixed-top").css("position") == "fixed") {
                                    heightFixed = $(".navbar-fixed-top").height() + $("#breadcrumbs").height()
                                }
                                $.scrollTo($("#gab_page_" + getURLParameter("id_gab_page")), 1000, {
                                    queue:true,
                                    offset:-heightFixed
                                });
                            }
                        });
                        $divToLoad.siblings('.cat-modif').slideToggle(500);
                    }
                    if (anchorFound === false && $("#gab_page_" + getURLParameter("id_gab_page")).length > 0) {

                        anchorFound = "#gab_page_" + getURLParameter("id_gab_page")
                    }

                }
            });
        })

        $.ajax({
            mode: 'dequeue',
            port: 'ajaxWhois'
        });

    }

    reloadState();


    $(".sort-move").live("click", function(e) {
        e.preventDefault()
    })

});

/**
 * Permet de récupérer des paramètres de l'url
 */
function getURLParameter(name) {
    return decodeURI(
        (RegExp(name + '=' + '(.+?)(&|$)').exec(location.search)||[,null])[1]
        );
}

