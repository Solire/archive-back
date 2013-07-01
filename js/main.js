var myModal = {
    confirm: function(heading, question, cancelButtonTxt, okButtonTxt, callback) {

        var confirmModal =
                $('<div class="modal hide fade">' +
                '<div class="modal-header">' +
                '<a class="close" data-dismiss="modal" >&times;</a>' +
                '<h3>' + heading + '</h3>' +
                '</div>' +
                '<div class="modal-body">' +
                '<p>' + question + '</p>' +
                '</div>' +
                '<div class="modal-footer">' +
                '<a href="#" class="btn  btn-small btn-default" data-dismiss="modal"><i class="icon-remove"></i> ' +
                cancelButtonTxt +
                '</a>' +
                '<a href="#" id="okButton" class="btn  btn-small btn-danger"><i class="icon-ok"></i> ' +
                okButtonTxt +
                '</a>' +
                '</div>' +
                '</div>');

        confirmModal.find('#okButton').click(function(event) {
            event.preventDefault()
            callback();
            confirmModal.modal('hide');
        });
        confirmModal.appendTo("body")
        confirmModal.modal('show');
    },
    message: function(heading, message, closeButtonTxt, closeDelay, widthAuto) {
        var appendClass = ""
        if(widthAuto) {
            appendClass = " modal-auto-width";
        }

        var messageModal =
                $('<div class="modal hide fade' + appendClass +'">' +
                '<div class="modal-header">' +
                '<a class="close" data-dismiss="modal" >&times;</a>' +
                '<h3>' + heading + '</h3>' +
                '</div>' +
                '<div class="modal-body">' +
                '</div>' +
                '<div class="modal-footer">' +
                '<a href="#" id="closeButton" data-dismiss="modal" class="btn  btn-small btn-primary">' +
                closeButtonTxt +
                '</a>' +
                '</div>' +
                '</div>');
        var message = $.type(message) === "string" ? '<p>' + message + '</p>' : message ;
        messageModal.find(".modal-body").append(message)
        messageModal.appendTo("body")
        messageModal.modal('show');
        if (closeDelay) {
            messageModal.delay(closeDelay).queue(function(nxt) {
                messageModal.modal("hide")
                nxt(); // continue the queue
            })
        }

    }


}

function rescale(){
    var size = {width: $(window).width() , height: $(window).height() }
    /*CALCULATE SIZE*/
    var offset = 20;
    var offsetBody = 150;
    $('.modal').each(function() {
        $('.modal-body', this).css('max-height', size.height - (offset + offsetBody));
        $(this).css('margin-top',  - $(this).height() / 2);
        //Si autoWidth
        if($(this).hasClass("modal-auto-width")) {
            $(this).width("auto")
            $('.modal-body', this).css('max-width', size.width - (offset + offsetBody));
            $(this).css('margin-left',  - $(this).width() / 2);
            $(this).css('margin-top',  - $(this).height() / 2);
        }
    })

}

$(function() {

    $(window).bind("resize", rescale);
    
    $('body').on('show', '.modal', function () {
        rescale()
        console.log("modal show event fired")
    });

    $(".visible-lang").live("click", function() {
        var $this = $(":checkbox", this);
        var value = $this.val().split("|")
        var id_gab_page = parseInt(value[0]);
        var id_version = parseInt(value[1]);
        if ($this.is(":checked")) {
            $this.removeAttr("checked");
        } else {
            $this.attr("checked", "checked");
        }
        var checked = $this.is(':checked');
        $.post(
                'back/page/visible.html',
                {
                    id_gab_page: id_gab_page,
                    id_version: id_version,
                    visible: checked ? 1 : 0
                },
        function(data) {
            if (data.status != 'success') {
                $this.attr('checked', !checked);
                $.sticky("Une erreur est survenue", {
                    type: "error"
                });
            }

            else {
                var $otherPageBloc = $('.visible-lang-' + id_gab_page + '-' + id_version).not($this)
                $otherPageBloc.attr('checked', checked);
                var $thisAll = $this.add($otherPageBloc)
                if (checked) {
                    $.sticky("La page a été rendue visible", {
                        type: "success"
                    });
                    $thisAll.each(function() {
//                        $(this).parents("li:first").removeClass("translucide")
                        var title = $(this).parents("a:first,button:first").attr("title");
                        $(this).parents("a:first,button:first").attr("title", title.substr(0, title.length - 19) + "invisible sur le site")
                        $(this).parents("a:first,button:first").find("i").removeClass("icon-eye-close translucide").addClass("icon-eye-open")
                    })
                } else {
                    $.sticky("La page a été rendue invisible", {
                        type: "success"
                    });
                    $thisAll.each(function() {
//                        $(this).parents("li:first").addClass("translucide")
                        var title = $(this).parents("a:first,button:first").attr("title");
                        $(this).parents("a:first,button:first").attr("title", title.substr(0, title.length - 21) + "visible sur le site")
                        $(this).parents("a:first,button:first").find("i").removeClass("icon-eye-open").addClass("icon-eye-close translucide")
                    })
                }
            }
        },
                'json'
                );
    })


    /*
     * Moteur de recherche Autocompletion sur les contenus
     */
    if ($(".live-search").length > 0)
        $(".live-search").livequery(function() {
            var appendTo = ".navbar-fixed-top";
            if ($(this).parents(".nav-search:first").length == 0) {
                appendTo = null
            }

            $(this).autocomplete({
                source: function(request, response) {

                    $.getJSON(
                            "back/page/livesearch.html",
                            {
                                term: request.term
                            }, function(data, status, xhr) {
                        response(data);
                    })
                },
                open: function() {
                    $(this).data("autocomplete").menu.element.hide().slideDown(150);
                },
                focus: function() {
                    return false
                },
                minLength: 2,
                appendTo: appendTo,
                select: function(e, ui) {
                    var baseHref = $("base").attr("href");
                    window.location.href = baseHref + "back/" + ui.item.url;
                    return false;
                }

            }).data("autocomplete")._renderItem = function(ul, item) {
                return $("<li></li>")
                        .data("item.autocomplete", item)
                        .append('<a><span>' + item.label + '</span><br /><span style="font-style:italic">&nbsp; > ' + item.gabarit_label + '</span></a>')
                        .appendTo(ul);
            };
        })




});

