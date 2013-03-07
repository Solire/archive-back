$(function(){
    var showVersion = function(current, otherId) {
        var tab = otherId.split('|'),
            other   = $('#langue_' + tab[0]);

        var version_suf = tab[1];

        $('.form-other-flag').html('<img src="img/flags/png/' + version_suf + '.png" />');

        $('.form-line', current).each(function(i){
            /** Variable selon l'élément */
            var div        = $('.form-line', other).eq(i),

                otherElmt = $('.form-controle', div),
                otherValue = getVersionValue(otherElmt),

                printLine  = $('.form-other-line', this),
                printElmt  = $('.form-other-elmt', this);

            printLine.slideDown(500);
            printVersionValue(printElmt, otherValue);
        });
    },
    getVersionValue = function(elmt){
        var tagName = String.toUpperCase(elmt[0].tagName),
            value;

        switch (tagName) {
            case 'SELECT' :
                value = $('option:selected').text();
                break;

            default :
                value = elmt.val();
                break;
        }

        return value;
    },
    printVersionValue = function(elmt, value){
        var tagName = String.toUpperCase(elmt[0].tagName);

        switch (tagName) {
            case 'IFRAME' :
                elmt[0].contentWindow.document.body.innerHTML = value;
                break;

            default :
                elmt.val(value);
                break;
        }
    };

    $('.langue').each(function(){
        var current = $(this),
            versionId    = $('[name="id_version"]', this).val(),
            otherId      = $('.compareversion-other', this).val();

        $('.compareversion-hide', current).hide();

        $('.compareversion-submit', current).click(function(){
            showVersion(current, otherId);
            $('.compareversion-hide', current).fadeIn(500);
        });

        $('.compareversion-hide', current).click(function(){
            $('.form-other-line', current).slideUp();
            $('.compareversion-hide', current).fadeOut(500);
        });
    });
});
