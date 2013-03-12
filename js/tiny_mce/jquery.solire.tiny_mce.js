(function($) {
    $.fn.tinymce = function(method) {
        var base = this,
            publicMethods = {}, aaa;

        function randomString(length)
        {
            var text = "";
            var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

            for( var i=0; i < length; i++ )
                text += possible.charAt(Math.floor(Math.random() * possible.length));

            return text;
        }

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
			if (publicMethods[name]) {
				publicMethods[name].push(func);
			} else {
				// No function, lets first make an array in the publicMethods object.
				publicMethods[name] = [func];
				// Defining the method that is actually called. Its only responsibility is to call the specified methods and make sure to return something meaningful.
				base[name] = function () {
					var functions = publicMethods[name],
                        returnvar,
                        numberofreturns = 0,
                        i,
                        tmpReturn;
					for (i = 0; i < functions.length; i++)
					{
						// Arguments is already defined, they are the arguments this method was called with.
						tmpReturn = functions[i].apply(base, arguments);
						if (tmpReturn != undefined) {
							numberofreturns++;
							if (numberofreturns == 1) {
								returnvar = tmpReturn;
							} else if (numberofreturns == 2) {
								returnvar = [returnvar, tmpReturn];
							} else {
								returnvar.push(tmpReturn);
							}
						}
					}
					if (numberofreturns == 0) {
						return base;
					}
					return returnvar;
				};
			}
		}

        base = this.each(function(){
            if (this.id === null || this.id === ''
                || $('[id=' + this.id + ']').length > 1
            ) {
                this.id = randomId();
                $(this).prop('tynimce-id', this.id);
            }

            function enable()
            {
                tinyMCE.execCommand('mceAddControl', false, this[0].id);
            }

            function disable()
            {
                tinyMCE.execCommand('mceFocus', false, this[0].id);
                tinyMCE.execCommand('mceRemoveControl', false, this[0].id);
                tinyMCE.triggerSave(true, true);
            }

            function change()
            {
                if(tinyMCE.getInstanceById(this[0].id)) {
                    this.disable();
                } else {
                    this.enable();
                }

                // tinyMCE.execCommand('mceToggleEditor',false,this.id);
            };

            function disableOnly()
            {
                if (tinyMCE.getInstanceById(this[0].id)) {
                    this.disable();
                }
            }

            function enableOnly()
            {
                if (!tinyMCE.getInstanceById(this[0].id)) {
                    this.enable();
                }
            }

            addMethod('disable', disable);
            addMethod('enable', enable);
            addMethod('disableOnly', disableOnly);
            addMethod('enableOnly', enableOnly);
            addMethod('change', change);
        });

        if (method in publicMethods) {
            eval('this.' + method + '()');
        };

        return base;
    };
})(jQuery);

