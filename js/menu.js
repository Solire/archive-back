$(function() {
	// set opacity to nill on page load
	$("ul#menu span").css("opacity","0");

	$("ul#menu span").hover(
		// on mouse over : animate opacity to full
		function () {
			$(this).stop().animate({
				opacity: 1
			}, 'slow');
		},
		// on mouse out : animate opacity to nill
		function () {
			$(this).stop().animate({
				opacity: 0
			}, 'slow');
		}
	);
        
    $('ul#menu li a[href="#"]').click(function(){
//        var eq = $('ul#menu li a').index($(this));
        var id = $(this).attr('id').split('_').pop();
        $('.sousmenu').hide();
        $('#sousmenu_' + id).show();
        
        return false;
    })
});