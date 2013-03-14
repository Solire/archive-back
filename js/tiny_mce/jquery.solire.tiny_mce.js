/**
 * @author thansen <thansen@solire.fr>
 * @licence solire.fr
 * 
 * @param jQuery $
 * 
 * @returns jQuery
 */
(function($) {
    /**
     * 
     * @param {string} method
     * 
     * @returns jQuery
     */
    $.fn.tinymce = function(method) {
        var base = this;
            publicMethods = {};

        /**
         * 
         * @param {int} length
         * 
         * @returns {String}
         */
        function randomString(length)
        {
            var text = '';
            var possible    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                            + 'abcdefghijklmnopqrstuvwxyz'
                            + '0123456789';

            for( var i=0; i < length; i++ )
                text += possible.charAt(Math.floor(Math.random() * possible.length));

            return text;
        }

        /**
         * Génére un attribut id unique (pour element HTML)
         * 
         * @returns {String}
         */
        function randomId()
        {
            do{
                tmpId = randomString(10);
            } while ($('#tinymce-' + tmpId).length > 0)

            return 'tinymce-' + tmpId;
        }

        /**
         * Adds a public method to the base element, to allow methods calls
         * to be made on the returned object.
         *
         * @param {String}   name
         * @param {function} func
         *
         * @return {void}
         */
		function addMethod(name, func) {
            publicMethods[name] = func;
            if (!(name in base)) {
                base[name] = function(){
                    base.each(function(){
                        func.apply($(this));
                    });
                };
            }
		}

        /**
         * 
         * 
         * @returns void
         */
        function enable()
        {
            tinyMCE.execCommand('mceAddControl', false, this[0].id);
        }

        /**
         * 
         * 
         * @returns void
         */
        function disable()
        {
            tinyMCE.execCommand('mceFocus', false, this[0].id);
            tinyMCE.execCommand('mceRemoveControl', false, this[0].id);
            tinyMCE.triggerSave(true, true);
        }

        /**
         * 
         * 
         * @returns void
         */
        function change()
        {
            if(typeof tinyMCE.getInstanceById(this[0].id) !== 'undefined') {
                this.disable();
            } else {
                this.enable();
            }

            // tinyMCE.execCommand('mceToggleEditor',false,this.id);
        };

        /**
         * 
         * 
         * @returns void
         */
        function disableOnly()
        {
            if (typeof tinyMCE.getInstanceById(this[0].id) !== 'undefined') {
                this.disable();
            }
        }

        /**
         * 
         * 
         * @returns void
         */
        function enableOnly()
        {
            if (typeof tinyMCE.getInstanceById(this[0].id) === 'undefined') {
                this.enable();
            }
        }
        
        function isFunc(func)
        {
            return $.isFunction(func);
        }

        addMethod('disable', disable);
        addMethod('enable', enable);
        addMethod('disableOnly', disableOnly);
        addMethod('enableOnly', enableOnly);
        addMethod('change', change);

        if (method in publicMethods && isFunc(publicMethods[method])) {
            publicMethods[method].apply(base);
        }

        return base.each(function(){
            if (this.id === null || this.id === ''
                || $('[id=' + this.id + ']').length > 1
            ) {
                this.id = randomId();
                $(this).attr('tynimce-id', this.id);
            }
        });
    };
})(jQuery);

