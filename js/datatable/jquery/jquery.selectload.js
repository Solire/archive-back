//My plugins
(function($) {
    
    $.fn.selectLoad = function (options) {
        var defaults = {
            callback: function() {},
            data: {}
        };
        
        function load() {
            var select = this
            var url = $(select).attr("data-url")
            var srcName = $(select).attr("data-column-key")
            var value = $(select).attr("data-value")
            var data = options.data;
            data = $.extend(data, {load : srcName});
            
            $.post(url, data, function (data) {              
                $(select).empty();
                if($(select).hasClass("ui-select-fac"))
                    $(select).append('<option value="0">&nbsp;</option>');
                $.each(data, function (key, item) {
                    $(select).append('<option ' + (value == key ? 'selected' : '') + ' value="' + key + '">' + item.name + '</option>');
                });
                
                
                
                if (typeof options.callback == 'function') { // make sure the callback is a function
                    options.callback.call(select, data); // brings the scope to the callback
                }

                
            }, 'json');
        }
        
        var options = $.extend(defaults, options);
        $(this).each(load);   

        // interface fluide
        return $(this);
    }
        
    
})(jQuery);