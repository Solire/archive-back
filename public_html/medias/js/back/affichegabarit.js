var extensionsImage = ['jpg', 'jpeg', 'gif', 'png'];
var sort_elmt = $(null);
var sortpar = $(null);
var basehref = '';

var initTinyMCE = function () {
    tinyMCE.init({
        mode: "none",
        theme : "advanced",
//      valid_elements : "a[href],em/i,strike,u,strong/b,div[align],br,#p[align],-ol[type|compact],-ul[type|compact],-li",
        language : "fr",
        plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
        width:"500px",
        height:"290px",
//      height:hauteur,

        // ne transforme plus les en html_entities
        entity_encoding : "raw",

//      // Sauts de ligne en <br/>
//      forced_root_block : false,
//      force_br_newlines : true,
//      force_p_newlines : false,

        // Theme options
        theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,formatselect,|,bullist,numlist,|,undo,redo,|,link,unlink,image",
        theme_advanced_buttons2 : "",
        theme_advanced_buttons3 : "",

        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_resizing : true,

        theme_advanced_blockformats : "h3,h4",

//        external_image_list_url : "../media/autocomplete.html?tinyMCE"
        
        
        relative_urls : true,
//        remove_script_host : false,
        convert_urls : true,
        document_base_url : "../../../../",
//        content_css : "css/back/style-tinymce.css",
        external_image_list_url : "back/media/autocomplete.html?tinyMCE"
    });    
}

initTinyMCE();

$(function(){
    $.cookie('id_gab_page', $('input[name=id_gab_page]').val(), {path : '/'});
    
	$.datepicker.regional['fr'] = {
		closeText: 'Fermer',
		prevText: 'Précédent',
		nextText: 'Suivant',
		currentText: 'Aujourd\'hui',
		monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin',
		'Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
		monthNamesShort: ['Janv.','Févr.','Mars','Avril','Mai','Juin',
		'Juil.','Août','Sept.','Oct.','Nov.','Déc.'],
		dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
		dayNamesShort: ['Dim.','Lun.','Mar.','Mer.','Jeu.','Ven.','Sam.'],
		dayNamesMin: ['D','L','M','M','J','V','S'],
		weekHeader: 'Sem.',
		dateFormat: 'dd/mm/yy',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''
	};

	$.fn.clearForm = function(){
		var idnew;
		
		this.find('input, textarea, select').not('[name="visible[]"]').not(".join-param").each(function(){
			idnew = $(this).attr('id')+'a';
			$(this).attr('id', idnew);
			$(this).prev('label').attr('for', idnew);

			if($(this).is('input'))
				$(this).val('');
			else{
				if($(this).is('textarea')){
					$(this).tinymce('disableOnly');
					$(this).val('');
				}
				else{
					if($(this).is('select'))
						$(this).val($(this).children('option:first').val());
				}
			}
		});
		
		this.find('.previsu').text('').attr('href', '');

		return this;
	}
	
	var tinyMethods = {
		disable : function(){
			//var base = this;
			$('#tempId').attr('id','');
			if(this.$el.attr('id')=='')
				this.$el.attr('id', 'tempId');
			var tinyId = this.$el.attr('id');

			tinyMCE.execCommand('mceFocus', false, tinyId);
			tinyMCE.execCommand('mceRemoveControl', false, tinyId);
			tinyMCE.triggerSave(true, true);
		},
		enable : function(){
			//var base = this;
			$('#tempId').attr('id','');
			if(this.$el.attr('id')=='')
				this.$el.attr('id', 'tempId');
			var tinyId = this.$el.attr('id');

			tinyMCE.execCommand('mceAddControl',false,tinyId);
		},
		change : function(){
			$('#tempId').attr('id','');
			if(this.$el.attr('id')=='')
				this.$el.attr('id', 'tempId');
			var tinyId = this.$el.attr('id');

			if(tinyMCE.getInstanceById(tinyId))
				tinyMethods['disable'].apply(this);
			else
				tinyMethods['enable'].apply(this);
		
//			tinyMCE.execCommand('mceToggleEditor',false,tinyId);
		},
		disableOnly : function(){
			$('#tempId').attr('id','');
			if(this.$el.attr('id')=='')
				this.$el.attr('id', 'tempId');
			var tinyId = this.$el.attr('id');
			
			if(tinyMCE.getInstanceById(tinyId)){
				tinyMethods['disable'].apply(this);
				this.$el.addClass('tinymce-tmp-disabled');
			}
		},
		enableOnly : function(){
			$('#tempId').attr('id','');
			if(this.$el.attr('id')=='')
				this.$el.attr('id', 'tempId');
			var tinyId = this.$el.attr('id');

			if(!tinyMCE.getInstanceById(tinyId))
				tinyMethods['enable'].apply(this);

			this.$el.removeClass('tinymce-tmp-disabled');
		}
	};
	
	$.tinymce = function(method, el){
		var base=this;
		base.$el = $(el);
        base.el = el;

		return tinyMethods[method].apply(this);
	}
	
	$.fn.tinymce = function(method){
		var tab = [];

		this.each(function(){
			tab.push(new $.tinymce(method, this));
		});
		return tab;
	};

	$('textarea').tinymce('enable');

	$('label > .switch-editor').live('click', function(){
		if($(this).parent().next().is('textarea')){
			if($(this).children().eq(0).hasClass('translucide')) {
				$(this).children().eq(0).removeClass('translucide');
				$(this).children().eq(1).addClass('translucide');
			}
			else{
				$(this).children().eq(0).addClass('translucide');
				$(this).children().eq(1).removeClass('translucide');
			}

			$(this).parent().next().tinymce('change');
		}
		
		return false;
	});

//// GESTION DU TRI
	$('.sort-box').each(function(){
		$(this).sortable({
			placeholder: 'empty',
			items: '.sort-elmt',
			handle: '.sort-move',
			deactivate: function() {
				//callback();
			},
			start: function(e, ui){
				$('textarea', ui.item).tinymce('disableOnly');
			},
			stop: function(e, ui){
				$('textarea.tinymce-tmp-disabled', ui.item).tinymce('enableOnly');
			}
		 });
	});
	
    $('.addBloc').live('click', function(){
		var $this = $(this).parents('.buttonright').first();
		var adupliquer = $this.prev();
		$('textarea', adupliquer).tinymce('disableOnly');
		var clone = adupliquer.clone(false).clearForm();
		clone.insertBefore($this);
		$this.parents('.sort-box').sortable('refresh');
		$this.siblings('.sort-elmt').find('.delBloc').removeClass('translucide');
		$this.find('.form-date').datepicker($.datepicker.regional['fr']);

		initAutocompletePat();

		$('textarea', adupliquer).tinymce('enableOnly');
		$('textarea', clone).tinymce('enableOnly');

		return false;
	});
	
	var confirm = $('<div>', {id : 'confirm'}).dialog({
		title : "Attention",
		buttons: {
			"Ok" : function(){
				if(sort_elmt.find('textarea').length > 0)
					sort_elmt.find('textarea').tinymce('disableOnly');

				sort_elmt.slideUp('fast', function(){
					if ($(this).siblings('.sort-elmt').length < 2)
						$(this).siblings('.sort-elmt').find('.delBloc').addClass('translucide');
					$(this).remove();
					sortpar.sortable('refresh');
				});

				$(this).dialog("close")
			},
			"Annuler" : function(){
				$(this).dialog("close");
			}
		},
		autoOpen : false,
		close: function(event, ui){
			sort_elmt = $(null);
		}
	});
	
	var previsu = $('<div>', {id : 'previsu'}).dialog({
		title : "Prévisualisation",
		autoOpen : false,
		close: function(event, ui){image = $(null);}
	});

	
///////////////////////////////
//// GESTION DES EVENMENTS ////

	$('.changevisible').live('click', function(){
		if($(this).is(':checked')){
			$(this).next().val(1);
			$(this).parent().first().next().removeClass('translucide');
		}
		else{
			$(this).next().val(0);
			$(this).parent().first().next().addClass('translucide');
		}
	});

	$('.delBloc').live('click', function(){
		if (!$(this).hasClass('translucide')) {
			sort_elmt = $(this).parents('.sort-elmt').first();
			sortpar = sort_elmt.parent();
			confirm.html("Etes-vous sur de vouloir supprimer ce bloc?");
			confirm.dialog('open');
		}
		return false;
	});
	
	$('.previsu').live('click', function(){
		image=$(this);
		var link=$(this).attr('href');
		previsu.html('<img src="'+link+'" />');
		previsu.dialog('open');
		
		return false;
	});
	
	$('legend').live('click', function(){
//		$(this).siblings('.line').toggle(500);
		$(this).next().slideToggle(500);
		return false;
	});

	$('.form-date').datepicker($.datepicker.regional['fr']);

	var version = 'version web';

	$('.changemedia').click(function(){
		$('.mobile_media').slideToggle(500);
		$('.web_media').slideToggle(500);
		$(this).children().text(version);
		version = version == 'version web' ? 'version mobile' : 'version web';
		$('#version_courante').text(version);
		return false;
	});
        
	function initAutocompletePat(){
		$('.form-file').each(function(){
            var tthis = $(this);
            
			tthis.autocomplete({
				source:'media/autocomplete.html',
				minLength: 0,
				select: function(event, ui) {
					if($(this).siblings('.previsu').length > 0)
						$(this).siblings('.previsu').text(ui.item.label).attr('href', ui.item.path);
					$(this).val(ui.item.value);
                    if (typeof ui.item.file_id != "undefined") $(this).addClass('atelecharger-' + ui.item.file_id)
					$(this).autocomplete("close");
					
					return false
				}
			});
            
			tthis.data("autocomplete")._renderItem = function(ul, item){
				var ext = item.value.split('.').pop();
				var prev = (extensionsImage.indexOf(ext)!=-1) ? '<img src="'+item.vignette+'" height="40" />' : '';
				return $( "<li></li>" )
					.data( "item.autocomplete", item )
					.append( '<a>'+item.label+'&nbsp;'+prev+'</a>' )
					.appendTo( ul );
			};
        
            tthis.data("autocomplete")._renderMenu = function( ul, items ) {
                var self = this;
                                
                if (uploader.files.length > 0) {
                    for (var i = 0; i < uploader.files.length; i++) {
                        if (uploader.files[i].percent == 0) {
                            $( "<li></li>" )
                                .data( "item.autocomplete", {label : uploader.files[i].name, path:'', vignette:'', value: uploader.files[i].name, file_id: uploader.files[i].id})
                                .append( '<a>'+uploader.files[i].name+'</a>' )
                                .appendTo( ul );
                        }
                    }
                }
                
                $.each( items, function( index, item ) {
                    self._renderItem( ul, item );
                });
            };
            
            tthis.data("autocomplete")._response = function( content ) {
                var contentlength = typeof uploader == "undefined" ? content.length : content.length + uploader.files.length;
                
                if ( !this.options.disabled && content && contentlength ) {
                    content = this._normalize( content );
                    this._suggest( content );
                    this._trigger( "open" );
                } else {
                    this.close();
                }
                this.pending--;
                if ( !this.pending ) {
                    this.element.removeClass( "ui-autocomplete-loading" );
                }
            };

		}).focusin(function(){
			$(this).autocomplete( "search" , $(this).val())
		})
	}

    initAutocompletePat();

	if ($('form').length > 1) {
		$('.openlang').click(function() {
			i = $('.openlang').index($(this));
			
			if($('.langue').eq(i).is(':hidden')) {
				$('.openlang.active').removeClass('active').addClass('translucide');
				$(this).removeClass('translucide').addClass('active');
				
				$('.langue:visible').slideUp(500);
				$('.langue').eq(i).slideDown(500);
			}
			
			return false;
		});

//		$('form').not('form:first').each(function(){
//			var formu = $(this);
//			$('.controle', formu).attr('autocomplete','off').tipsy({
//				trigger: 'focus',
//				gravity: 'n',
//				opacity: 1,
//				html: true,
//				title: function(){
//					var eq = $('.controle', formu).index($(this));
//					return $('form:first .controle').eq(eq).val();
//				}
//			});
//		});
	}
    
//////////////////// PLUPLOAD ////////////////////
    basehref = $('base').attr('href');
    
	uploader = new plupload.Uploader({
		runtimes : 'gears,html5,silverlight,flash,html4',
		browse_button : 'pickfiles',
		max_file_size : '1000mb',
		chunk_size : '7mb',
		url : basehref + 'media/upload.html',
		flash_swf_url : basehref + 'js/admin/plupload/plupload.flash.swf',
		silverlight_xap_url : basehref + 'js/admin/plupload/plupload.silverlight.xap',
		filters : [
			{title : "Image files", extensions : "jpg,jpeg,gif,png"},
			{title : "Zip files", extensions : "zip,rar,bz2"},
			{title : "Adobe", extensions : "pdf,eps,psd,ai,indd"}
		],
		drop_element : 'colright',
		unique_names : false,
		multiple_queues : true
	});

	uploader.bind('Init', function(up, params) {
		$('#currentruntime').text("Current runtime: " + params.runtime);
	});

	var uploaderInited = false;
    
    var uploaderInit = function(){
        if (!uploaderInited) {
            uploaderInited = true;

            uploader.init();

            uploader.bind('FilesAdded', function(up, files) {
                $.each(files, function(i, file) {
                    var tr, td;
                    if(!file.error) {
                        tr = $('<tr>');
                        $('<td>', {colspan : 4}).html(file.name + '<div class="progressbar"></div>').appendTo(tr);
                        file.tr = tr;
                    }
                    else
                        uploader.splice(i, 1);
                });

                $.each(files, function(i, file) {
                    if(!file.error) {
                        if (i==0)
                            file.tr.prependTo($('#foldercontent'));
                        else
                            file.tr.insertAfter(files[i-1].tr);
                    }
                });

                $('.progressbar').progressbar({value: 0});

                up.refresh(); // Reposition Flash/Silverlight
                if (parseInt($('[name=id_gab_page]').val()) > 0)
                    up.start();
            });

            uploader.bind('UploadProgress', function(up, file) {
                $('.progressbar', file.tr).progressbar("value", file.percent);
            });

            uploader.bind('Error', function(up, err) {
                err.file.error = true;
                up.refresh(); // Reposition Flash/Silverlight
            });

            uploader.bind('FileUploaded', function(up, file, info) {

                $(file.tr, '.progressbar').progressbar("destroy");

                var response = $.parseJSON(info.response);

                if(response.status != "error") {
                    var ligne = '';

                    ligne += '<td><a href="' + response.path + '" id="fileid_' + response.id + '" target="_blank" class="previsu">';

                    var ext = file.name.split('.').pop().toLowerCase();
                    if (extensionsImage.indexOf(ext) != -1)
                        ligne += '<img class="vignette" src="' + response.minipath + '" alt="' + ext + '" /></a></td>';
                    else
                        ligne += '<img class="vignette" src="styles/admin/images/' + ext + '.png" alt="' + ext + '" /></a></td>';

                    ligne += '<td>' + response.size + '</td>';
                    ligne += '<td>' + response.date.substr(0, 10) + '<br />' + response.date.substr(11) + '</td>';
                    ligne += '<td><a href="' + response.path + '" class="previsu button bleu"><span class="bleu"><img alt="supprimer" src="img/back/voir.png" /></span></a></td>';

                    file.tr.attr("id", "fileid_" + response.id);
                    file.tr.html(ligne);
                }
                else {
                    file.tr.remove();
                }
                
                uploader.splice(0, 1);
                
                if (formsubmit.done && formsubmit.search) {
                    document.location.search = formsubmit.search;
                }
            });
        }
        else
            uploader.refresh();
    }
    
    $('#pickfiles').live('click', function(){return false;})

    var uploader_popup = $('<div>', {id : 'uploader_popup'}).load('media/popuplistefichiers.html', function(){
        $(this).dialog({
            autoOpen : false
        });
                
        $('.uploader_popup').click(function(){
            uploader_popup.dialog("open");
//            uploader.init();
            uploaderInit();
//            uploader.refresh();
            
            return false;
        });
    });
        
        
//	$('.jointure-autocomplete').each(function(){
//        var id_gabarit = $(this).attr('id').split('_').pop();
//        var $content = $(this).siblings('.jointure-autocomplete-content');
//        
//        $(this).autocomplete({
//            source : function(request, response) {
//                var deja = [];      
//                $('[name="jointure[' + id_gabarit + '][]"]', $content).each(function(){
//                    deja.push($(this).val());
//                });
//
//                $.getJSON('page/autocomplete.html', {term : request.term, id_gabarit : id_gabarit, deja : deja}, response);
//            },
//            minLength : 0,
//            select: function(event, ui) {
//                console.log(parseInt(ui.item.value));
//                
//                var content = '<div class="sort-elmt ligne" id="gab_page_' + ui.item.value + '">'
//                            + '<input type="hidden" value="' + ui.item.value + '" name="jointure[' + id_gabarit + '][]">'
//                            + '<a style="float: right;" class="button bleu delBloc" href="#"><span class="bleu"><img src="img/back/supprimer.png" alt="Supprimer"></span></a>'
//                            + '<a class="button bleu sort-move" href="#"><span class="bleu"><img src="img/back/deplacer.png" alt="déplacer"></span></a>'
//                            + '<a class="button bleu"><span class="bleu" style="padding-top:8px;"><input type="checkbox"' + (parseInt(ui.item.visible) > 0 ? ' checked="checked"' : '') + ' class="rendrevisible"></span></a>'
//                            + '<a target="_blank" style="margin-left:20px;" href="page/display.html?id_gab_page='
//                            + ui.item.value
//                            + '"><b>'
//                            + ui.item.label
//                            + '</b></a></div>';
//                        
////                    '<a class="jointure-elmt" href="#"><input type="hidden" value="' + ui.item.value + '" name="jointure[' + id_gabarit + '][]"><span>' + ui.item.label + '</span><span class="ui-icon ui-icon-closethick">close</span></a>';          
//                
//                $content.append(content);
//                
//                if ($('.sort-elmt', $content).length > 1)
//                    $('.sort-elmt .delBloc', $content).removeClass('translucide');
//                
//                $content.sortable('refresh');
//                $(this).val('');
//
//                return false;
//            }
//        }).focusin(function(){
//            $(this).keyup()
//        });
//    });
    
	$('.rendrevisible').live('click', function(){
		var $this = $(this);
		var id_gab_page = parseInt($this.parents('.sort-elmt').first().attr('id').split('_').pop());
		var checked = $this.is(':checked');
		
		$.post(
			'page/visible.html',
			{
				id_gab_page : id_gab_page,
				visible     : checked ? 1 : 0
			},
			function(data){
//                console.log(data);
                
				if(data.status != 'success')
					$this.attr('checked', !checked);
			},
            'json'
		);
	});

});