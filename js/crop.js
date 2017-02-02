var openCropDialog;
$(function(){
    /**
     * Redimensionnement et recadrage des images
     *
     * Create variables (in this scope) to hold the API and image size
     */
    var jcrop_api, boundx, boundy, $inputFile, $inputAlt, crop = false;

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

            crop = true;
//            $('.form-crop-submit').removeClass('disabled')

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
        $inputAlt.val($('#image-alt').val());

        if (!crop) {
            $('#modalCrop').modal('hide');
            return false;
        }
        var action = 'back/' + $('.form-crop').attr('action')
            + '?gabaritId=' + $('[name=id_gabarit]').val();

        var data = $('.form-crop').serialize();
        $.post(action, data, function(response) {
            $('#modalCrop').modal('hide');
            $inputFile.val(response.filename);
            $inputFile.siblings('.previsu').attr('href', response.path);
            
            var previsu = $inputFile.parent().siblings('.previsu');

            if (previsu.length > 0) {
                previsu.attr('href', response.path);
                previsu.show();
                $('.champ-image-value', previsu).text(response.value);

                $('.champ-image-size', previsu).text(response.size).show();
                $('.champ-image-size', previsu).prev().show();

                $('.champ-image-vignette', previsu).attr('src', response.vignette).show();
               
            }
            
        }, 'json');

    });

    $('.crop').live('click', function(e) {
        e.preventDefault();
        openCropDialog.call(this);
    });

    $('.solire-js-empty').live('click', function(e) {
        e.preventDefault();
        $(this).siblings('input[type=text]').val('');
        $(this).hide();
        $(this).siblings('.crop').hide();
        $(this).parent().siblings('.previsu').hide();
    });

    openCropDialog = function(){
        var aspectRatio = 0,
            visuelId,
            fieldset;

        $('.img-info, .expected-width, .expected-height, .expected-width-height').hide();
        $('.force-selection input').attr('checked', 'checked');

        $('.wShow').html('');
        $('.hShow').html('');

        var src = $(this).parent().siblings('.previsu').attr('href');

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
            crop = false;
//            $('.form-crop-submit').addClass('disabled');

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
});
