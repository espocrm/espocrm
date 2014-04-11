(function ($) {

	var root = this;
	
	root.Espo = {};

	this.EspoTest = {
		
		include: function (path, options) {
			var thisObject = root;			
			if (options != undefined && 'self' in options) {
				thisObject = options.self;
			}
			
			var self = this;
				
			var ajaxOptions = {
				url: path,
				async: false,
				cache: false,
				type: 'GET',
				dataType: 'text',
				success: function (script) {
					eval.call(thisObject, script);
				},
				error: function () {
					console.error("Error occured while loading " + path);
				}
			};
		
			if (typeof options != 'undefined') {
				_.each(options, function (value, key) {
					ajaxOptions[key] = options[key];
				});
			}
			
			$.ajax(ajaxOptions);		
		},
	
	};
}).call(this, $);
