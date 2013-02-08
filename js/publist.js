$(function(){
    var sort_elmt, sort_box, positions = {};
    
//// SUPPRIMER UNE PAGE.
	var confirm = $('<div>', {id : "confirm"}).dialog({
		autoOpen : false,
		title : "Attention",
		buttons: {
			"Ok" : function(){
				$.post('pub/delete.html', {id_pub : parseInt(sort_elmt.attr('id').split('_').pop())}, function(data){
					if(data.status == 'success')
						sort_elmt.slideUp('fast', function(){
							$(this).remove();
							sort_box.sortable('refresh');
							$('#confirm').dialog("close")
						})
				}, 'json')
			},
			"Annuler" : function(){$(this).dialog("close");}
		}
	});
	
	$('.supprimer').live('click', function(){
		sort_elmt = $(this).parents('.sort-elmt').first();
		sort_box = sort_elmt.parent();
        
        if (sort_box.length > 0 && sort_elmt.length >0) {
            confirm.html("Etes-vous sur de vouloir supprimer cette publicité?");
            confirm.dialog('open');
        }

		return false
	});

	$('.rendrevisible').live('click', function(){
		var $this = $(this);
		var id_pub = parseInt($this.parents('.sort-elmt').first().attr('id').split('_').pop());
		var checked = $this.is(':checked');
		
		$.post(
			'pub/visible.html',
			{
				id_pub : id_pub,
				visible     : checked ? 1 : 0
			},
			function(data){
				if(data.status != 'success')
					$this.attr('checked', !checked);
			},
            'json'
		);
	});

	var initTri = function () {
		$('.sort-box').each(function(){
			var i = 1;
			$(this).children().each(function(){
				positions[parseInt($(this).attr('id').split('_').pop())] = i++;
			});

			$(this).sortable({
				placeholder: 'empty',
				items: '> .sort-elmt',
				handle: '.sort-move',
				deactivate: function(){
					var i = 1;
					$(this).children().each(function(){
						positions[parseInt($(this).attr('id').split('_').pop())] = i++;
					});
				}
			 });
		});
	}

	initTri();

	$('a.enregistrerordre').click(function(){
		$.post('pub/order.html', {'positions' : positions }, function(data){
			$("a.enregistrerordre span").text(data);
			window.setTimeout('$("a.enregistrerordre span").text("Enregistrer Ordre")', 2000);
		});

		return false;
	});

});