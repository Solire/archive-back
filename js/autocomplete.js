var timer = null;

$(function(){
    var autocompleteParams = function(join, id_champ, sortBox, id_version, id_gab_page){
            return {
                source      : function(request, response) {
                    var ids = [];
                    $('[name="champ' + id_champ + '[]"]', sortBox).not(join).each(function(){
                        var v = $(this).val();
                        if (v !== '' && !isNaN(parseInt(v))) {
                            ids.push(v);
                        }
                    });

                    $.getJSON(
                        'back/page/autocompletejoin.html',
                        {
                            term         : request.term,
                            ids          : ids,
                            id_champ     : id_champ,
                            id_version   : id_version,
                            id_gab_page  : id_gab_page
                        },
                        function(data, status, xhr) {
                            response(data);
                        }
                    );
                },
                minLength   : 0,
                select      : function(e, ui) {
                    $(this).next('.join').val(ui.item.id);
                }
            };
        },
        initAutocomplete = function() {
            var form = $(this).parents('form'),
                id_version = $('[name=id_version]', form).val(),
                id_gab_page = $('[name=id_gab_page]', form).val(),
                sortBox = $(this).parents('.sort-box'),
                join = $(this).next('.join'),
                id_champ = join.attr('name').match(/champ(\d+)\[\]/).pop();

            $(this).autocomplete(
                autocompleteParams(join, id_champ, sortBox, id_version, id_gab_page)
            ).prop(
                'opentimer',
                0
            ).focus(function(){
                var self = this,
                    timer = $(this).prop('opentimer');

                if (this.value == '') {
                    clearTimeout(timer);
                    timer = setTimeout(
                        function(){
                            if ($(self).val() == '') {
                                $(self).autocomplete('search', '');
                            }
                        },
                        220
                    );
                    $(this).prop('opentimer', timer);
                }
            }).keyup(function(){
                $(this).next('.join').val('');
            });
        };

    $('.autocomplete-join', this).each(initAutocomplete);

    addBlocCallback.push(function(sortBox, clone){
        $('.autocomplete-join', clone).each(initAutocomplete);
    });

    $('.autocomplete-link').livequery(function(){
        var $input = $(this);
        $(this).autocomplete({
            source: function( request, response ) {
                $.getJSON(
                    'sitemap.xml?json=1&visible=0',
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
        }).focus(function() {
            if (this.value == "") {
                clearTimeout(timer);
                timer = setTimeout(
                    function(){
                        if ($input.val() == '') {
                            $input.autocomplete('search', '');
                        }
                    },
                    220
                );
            }
        }).data( "autocomplete" )._renderItem = function( ul, item ) {
            return $( "<li></li>" )
            .data( "item.autocomplete", item )
            .append( "<a><span" + (item.visible == "1" ? '' : ' style="opacity: 0.6;"' ) + ">" + item.title + "</span></a>" )
            .appendTo( ul );
        };
    });
});
