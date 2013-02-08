(function($){
	$.fn.scrollLoad = function( options ) {

		var defaults = {
			url : '',
			data : '',
			ScrollHeight : 10,
			onload : function(data, box) {
				$(box).append(data);
			},
			start : function( itsMe ){

            },
			continueWhile : function(data) {
				return true;
			},
			getData : function( itsMe ) {
				return '';
			}
		};

		var Opts = $.extend(defaults, options);

        var BrowserHeight, Box = this;
        Box.scrolling = false;
        if($.browser.msie){
            BrowserHeight = $(window).height();
        } else {
            BrowserHeight = window.innerHeight;
        }

        $(window).scroll(function (e) {
            if( Box.scrolling ) return;

            if ($(window).scrollTop() == 0)
                return;

            if (Math.round(($(Box).height() - ($(window).scrollTop() + BrowserHeight)) / ($(Box).height() + $(Box).position().top) * 100 ) < Opts.ScrollHeight) {
                Opts.start.call(Box, Box);
                Box.scrolling = true;
                $this = $(Box);
                $.ajax({
                    url : Opts.url,
                    data : Opts.getData.call(Box, Box),
                    type : 'post',
                    success : function(data) {
                        Box.scrolling = false;
                        Opts.onload.call($this, data, $this);
                        if(!Opts.continueWhile.call($this, data)) {
                            $(window).unbind('scroll');
                        }
                    }
                });
            }
        });
	}
})( jQuery );
