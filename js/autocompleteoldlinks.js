var timer = null;

$(function(){

    $("input[name=new], table td:nth-child(2) input").livequery(function(){
        var $input = $(this);
        $(this).autocomplete({
            source: function( request, response ) {

            $.getJSON(
                "../sitemap.xml?json=1&visible=0",
                {
                term : request.term
                }, function( data, status, xhr ) {
                response( data );
                })
            },
            minLength: 0,
            select: function(e, ui) {
                $input.val(ui.item.path)
                return false;
            }
            }).focus( function() {

            if (this.value == "")
            {
            clearTimeout(timer);
            timer = setTimeout(function(){
                if ($input.val() == "")
                {
                $input.autocomplete('search', '');
                }
                },220);

            }
            }).data( "autocomplete" )._renderItem = function( ul, item ) {
            return $( "<li></li>" )
            .data( "item.autocomplete", item )
//            .append( "<a>" + item.title + "<br>" + item.path + "</a>" )
            .append( "<a><span" + (item.visible == "1" ? '' : ' style="opacity: 0.6;"' ) + ">" + item.title + "</span></a>" )
            .appendTo( ul );
        };
    });

    $("input[name=old], table td:first-child input").livequery(function(){
        var $input = $(this);
        $(this).autocomplete({
            source: function( request, response ) {


                $.getJSON(
                    "back/page/autocompleteoldlinks.html",
                    {

                        term : request.term
                    }, function( data, status, xhr ) {
                        response( data );
                    })
            },
            minLength: 0,
            select: function(e, ui) {
                $(this).val(ui.item.label)
            }

        }).focus( function() {

            if (this.value == "")
            {
                clearTimeout(timer);
                timer = setTimeout(function(){
                    if ($input.val() == "")
                    {
                        $input.autocomplete('search', '');
                    }
                },220);

            }
        })

    });





});