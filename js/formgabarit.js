var formsubmit = {
    elmt : $(null),
    done : false,
    search : ''
}

var expressions = {
    gmap_point_lat		: /\-?[0-9]+[\.]{0,1}[0-9]*/, // latitude
    gmap_point_lng		: /\-?[0-9]+[\.]{0,1}[0-9]*/, // longitude
    gmap_point_zoom		: /^[0-9]{1,2}$/, // num de 0 à 99 (zoom gmap)
    txt		: /^[a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s]{2,}$/, // texte uniquement
    txt2	: /^[0-9a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s]{2,}$/, // texte uniquement
    num		: /^[0-9]+$/, // chiffres et nombres uniquement
    num2	: /^[1-9]{1}|[0-9]{2,16}$/, // chiffres et nombres uniquement
    tel		: /^[ /\()+.0-9]{10,20}$/, // No tél
    cp		: /^[0-9]{4,5}$/, // code postal
    heure	: /^[0-9]{2,2}:[0-9]{2,2}$/, // date
    date	: /^[0-9]{2,2}\/[0-9]{2,2}\/[0-9]{4,4}$/, // date
    mail	: /^[a-z0-9._-]+@[a-z0-9.-]{2,}[.][a-z]{2,3}$/, // email
    url		: /^(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/, // adresse url
    rew		: /^[0-9a-z-]{1,50}$/ // rewriting
};

var json;

function verifieForm(elmt){
    var val = elmt.val(),
        testdonnee,
        classes,
        oblig,
        typeDonnee;

    if (!elmt.hasClass('form-controle')) {
        return true;
    }

    testdonnee  = false;
    classes     = elmt.attr('class').split(' ');
    if (classes.length < 3) {
        return true;
    }
    oblig       = classes[$.inArray('form-controle', classes) + 1] === 'form-oblig' || val !== '';
    typeDonnee  = classes[$.inArray('form-controle', classes) + 2].replace('form-', '');

    if (oblig) {
        if (typeDonnee in expressions) {
            expcourrante = expressions[typeDonnee];
            testdonnee = expcourrante.test(val);
        }
        else{
            if(typeDonnee == 'mix' || typeDonnee == 'file')
                testdonnee = val.length > 2;
            else{
                if(typeDonnee == 'notnul')
                    testdonnee = (val != '' && val != null && val != '0');
            }
        }

        if(testdonnee==false){
            elmt.parent().addClass('error');
            return false;
        }
    }
    elmt.parent().removeClass('error');
    return true;
}

/**
 * Gestion des evenements
 */
$(function(){
    $('.form-date.form-controle').live('change', function(){
        verifieForm($(this));
    });
    $('.form-controle').live('focusout', function(){
        if(typeof tinyMCE=="object")
            tinyMCE.triggerSave(true, true);

        verifieForm($(this));
    });



    var enregistrement = $('<div>', {
        id : 'enregistrement'
    }).dialog({
        autoOpen : false,
        title : "Enregistrement",
        height : "auto"
    });

    var enregistrement_open = function(string){
        enregistrement.html(string).dialog("open");
    }

    var enregistrement_change = function(string){
        enregistrement.html(string);
    }

    var enregistrement_close = function(){
        enregistrement.dialog("close");
    }

    $('.formajaxsubmit:visible').live('click', function(){

        json = {};
        var tthis = $(this);
        var ok = true;
        var formu = tthis.parents('form').first();

        if(typeof tinyMCE == "object")
            tinyMCE.triggerSave(true, true);

        $('input, select, textarea', formu).each(function(){
            if ($(this).attr('name') != "") {
                if (!verifieForm($(this)) && this.type != 'checkbox') {
                    var $this = $(this);
                    if ($(this).is('textarea')) $this = $(this).next();

                    if(ok){
                        ok = false;
                        var haut = $(this).parent().position().top - 10;
                        $('html').animate(
                        {
                            scrollTop : haut
                        },
                        'fast',
                        function(){
                            if($this.is(':hidden')){
                                var parent = $this.parents('fieldset').first();
                                if(parent.is(':hidden')){
                                    parent.parents('fieldset').first().children('legend').click();
                                }
                                parent.children('legend').click();
                            }
                            $this.focus();
                        }
                        );
                    }
                }
            }
        });

        if (ok) {
            $('.formajaxsubmit:visible').unbind("click");
            json = formu.serialize();

            //            enregistrement_open('<p>...</p>');

            $.sticky("Enregistrement en cours, veuillez patientez ...", {
                type:"notice"
            });




            $.post(
                formu.attr('action'),
                json,
                function(data){
                    $(formu).delay(500).queue(function(){
                        $("body").find(".sticky-queue").html("")

                        if(data.status == "success") {
                            var message = "La page a été enregistrée avec succès";
                            if(data.message) {
                                message = data.message;
                            }
                            $.sticky(message, {
                                type:"success"
                            });
                            if(data.javascript) {
                                $(formu).delay(500).queue(function(){
                                    eval(data.javascript)
                                    $(formu).dequeue()
                                });
                            }
                        } else {
                            var message = "Une erreur est survenue pendant l'enregistrement de la page";
                            if(data.message) {
                                message = data.message;
                            }
                            $.sticky(message, {
                                type:"error"
                            });
                        }
                        $(formu).dequeue()
                    })

                    //					enregistrement_change('<p>' + (data.status == "success" ? "Succès" : "Echec") + '</p>');
                    //                    window.setTimeout(enregistrement_close, 2000);
                    $("body").delay(1500).queue(function(){
                        if (data.status == "success") {

                            if (typeof data.id_gab_page != "undefined") {
                                $.cookie("id_gab_page", data.id_gab_page, {
                                    path : '/'
                                });
                                $('[name=id_gab_page]').val(data.id_gab_page);

                            }

                            if (typeof uploader == "object" && uploader.files.length > 0) {
                                uploader.start();
                                formsubmit.done = true;
                                formsubmit.elmt = formu;
                                if (data.search)
                                    formsubmit.search = data.search
                            }
                            else {
                                if (data.search)
                                    document.location.search = data.search;
                            }
                        }
                        $("body").dequeue()
                    });

                },
                'json'
                );
        }

        return false;
    });


    $('.formprev').click(function(){
        var formu = $(this).parents('form').first();
        formu.attr('target', '_blank');
        var oldaction =  formu.attr('action');
        formu.attr('action', './');

        if(typeof tinyMCE=="object")
            tinyMCE.triggerSave(true, true);

        formu.submit();

        formu.attr('target', '_self');
        formu.attr('action', oldaction);

        return false;
    });

});