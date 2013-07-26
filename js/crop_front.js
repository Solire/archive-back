/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

var idGabarit
$(function(){
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
            
            $(".form-crop-submit").removeClass("disabled")

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
        if($(this).hasClass("disabled"))
            return false;
        var action = "back/" + $(".form-crop").attr("action") + "?id_gab_page=" + idGabarit;
        var data = $(".form-crop").serialize();
        $.post(action, data, function(response) {
            $('#modalCrop').modal("hide");
            $inputFile.val(response.filename_front);
            $inputFile.siblings(".previsu").attr("href", response.filename_front);
            loadSelectImages($inputFile.parents("#mercury_media:first")[0])
        }, "json");
    });

    $(".crop").live("click", function(e) {
        var aspectRatio = 0;

        $(".img-info, .expected-width, .expected-height, .expected-width-height").hide();
        $(".force-selection input").attr("checked", "checked");

        e.preventDefault();
        $('.wShow').html("");
        $('.hShow').html("");

        var src = $(this).siblings('.previsu').attr("href");

        $inputFile = $(this).siblings("#media_image_url");

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
            $(".form-crop-submit").addClass("disabled")
            
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
            var filename = imageName.substr(imageName.indexOf('/') + 1);
            idGabarit = imageName.substr(0, imageName.indexOf('/'));


            $("#image-name").val(filename);
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
})