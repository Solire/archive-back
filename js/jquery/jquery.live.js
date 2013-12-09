(function($){
    if (typeof $.fn.live != 'undefined') {
        return;
    }

    /**
     * $("a.foo").live("click", fn), for example, you can write $(document).on("click", "a.foo", fn).
     */

    /**
     *
     * @param {type} event
     * @param {type} handler
     * @returns {undefined}
     */

    $.fn.live = function(event, handler) {
        $(document).on(event, this, handler);
    }
})(jQuery);

