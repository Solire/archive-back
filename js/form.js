var expressions = {
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
	val = elmt.val();
	
	if (!elmt.hasClass('controle'))
		return true;
	
	var testdonnee = false;
	var classes = elmt.attr('class').split(' ');
	var oblig = classes[$.inArray('controle', classes) + 1] == 'oblig' || (val != '' && val != '0');
	var typeDonnee = classes[$.inArray('controle', classes) + 2];
	
	if (oblig) {
		if (typeDonnee in expressions) {
			expcourrante = expressions[typeDonnee];
			testdonnee = expcourrante.test(val);
		}
		else{
			if(typeDonnee == 'mix' || typeDonnee == 'file')
				testdonnee = val.length>2;
			else{
				if(typeDonnee == 'notnul')
					testdonnee = (val!='' && val!='0');
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

$(function(){
	//envoi normal.
	$('.formsubmit').live('click', function(){
		var formu = $(this).parents('form').first();
		var ok = true;
		formu.attr('target', '');
		
//		formu.attr('action', actionTab[index]);

		if(typeof tinyMCE=="object")
			tinyMCE.triggerSave(true, true);
		
		$('.controle', formu).each(function(){
			if(!verifieForm($(this))){
				var $this = $(this);
				if ($(this).is('textarea')) $this = $(this).next();
				
				if(ok){
					ok = false;
					var haut = $(this).parent().position().top-10;
					$('html').animate(
						{scrollTop : haut},
						'fast',
						function(){
							if($this.is(':hidden')){
								var parent = $this.parents('fieldset').first();
								parent.children('legend').click();
								if(parent.is(':hidden'))
									$this.parents('fieldset').first().children('legend').click();
							}
							$this.focus();
						}
					);
				}
			}
		});
		
		if(ok)
			formu.submit();
		
		return false;
	});
    
    
    
	$('.controle').live('focusout', function(){
		if(typeof tinyMCE=="object")
			tinyMCE.triggerSave(true, true);
		
		verifieForm($(this));
	});

	//envoi normal.
	$('.formsubmit').live('click', function(){
		var formu = $(this).parents('form').first();
		var ok = true;
		formu.attr('target', '');
		
//		formu.attr('action', actionTab[index]);

		if(typeof tinyMCE=="object")
			tinyMCE.triggerSave(true, true);
		
		$('.controle', formu).each(function(){
			if(!verifieForm($(this))){
				var $this = $(this);
				if ($(this).is('textarea')) $this = $(this).next();
				
				if(ok){
					ok = false;
					var haut = $(this).parent().position().top-10;
					$('html').animate(
						{scrollTop : haut},
						'fast',
						function(){
							if($this.is(':hidden')){
								var parent = $this.parents('fieldset').first();
								parent.children('legend').click();
								if(parent.is(':hidden'))
									$this.parents('fieldset').first().children('legend').click();
							}
							$this.focus();
						}
					);
				}
			}
		});
		
		if(ok)
			formu.submit();
		
		return false;
	});

    var enregistrement = $('<div>', {id : 'enregistrement'}).dialog({
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
							{scrollTop : haut},
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
			json = formu.serialize();
			
            enregistrement_open('<p>...</p>');

			$.post(
				formu.attr('action'),
				json,
				function(data){
					enregistrement_change('<p>' + (data.status == "success" ? "Succès" : "Echec") + '</p>');
					window.setTimeout(enregistrement_close, 2000);
                    
                    if ('redirect' in data)
                        document.location.href = data.redirect;
				},
				'json'
			);
		}
		
		return false;
	});

    
    
    
});