$(function() {
    
    $(".visible-lang").live("click", function() {
        var $this = $(this);
        var value = $(this).val().split("|")
        var id_gab_page = parseInt(value[0]);
        var id_version = parseInt(value[1]);
        var checked = $this.is(':checked');
        $.post(
            'page/visible.html',
            {
                id_gab_page : id_gab_page,
                id_version : id_version,
                visible : checked ? 1 : 0
            },
            function(data){
                if(data.status != 'success') {
                    $this.attr('checked', !checked);
                    $.sticky("Une erreur est survenue", {
                        type:"error"
                    });
                }
                    
                else {
                    var $otherPageBloc = $('.visible-lang-' + id_gab_page + '-' + id_version).not($this)
                    $otherPageBloc.attr('checked', checked);
                    var $thisAll = $this.add($otherPageBloc)
                    if(checked) {
                        $.sticky("La page a été rendue visible", {
                            type:"success"
                        });
                        $thisAll.each(function() {
                            $(this).parents("li:first").removeClass("translucide")
                        })
                    } else {
                        $.sticky("La page a été rendue invisible", {
                            type:"success"
                        });
                        $thisAll.each(function() {
                            $(this).parents("li:first").addClass("translucide")
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
    if($(".live-search").length > 0 )
        $(".live-search").livequery(function() {
            var appendTo = ".navbar-fixed-top";
            if ($(this).parents(".nav-search:first").length == 0) {
                appendTo = null
            }
            
            $(this).autocomplete({
                source: function( request, response ) {
                
                $.getJSON( 
                    "page/live-search.html", 
                    {
                    term : request.term
                    }, function( data, status, xhr ) {
                    response( data );
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
                window.location.href = baseHref + ui.item.url;
                return false;
                }
                
                }).data( "autocomplete" )._renderItem = function( ul, item ) {
                return $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append( '<a><span>' + item.label + '</span><br /><span style="font-style:italic">&nbsp; > ' + item.gabarit_label + '</span></a>' )
                .appendTo( ul );
            };
        })
        
    
    

});

