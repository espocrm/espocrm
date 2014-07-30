/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/ 

Espo.define('Views.Stream.List', 'Views.Record.ListExpanded', function (Dep) {

	return Dep.extend({
	
		type: 'listStream',
		
		showTotalCount: false,
	
		buildRow: function (i, model, callback) {
			var key = 'row-' + model.id;
			this.rows.push(key);
			
			var type = model.get('type');						
			var viewName = 'Stream.Notes.' + type;

			this.createView(key, viewName, {
				model: model,
				acl: {
					edit: this.getAcl().checkModel(model)// this.getUser().isAdmin() || model.get('createdById') == this.getUser().id
				},
				isUserStream: this.options.isUserStream,
				optionsToPass: ['acl'],
				name: this.type + '-' + model.name,
				el: this.options.el + ' li[data-id="' + model.id + '"]'
			}, callback);

		},
	
		buildRows: function (callback) {
			this.checkedList = [];
			this.rows = [];

			if (this.collection.length > 0) {
				this.wait(true);
									
				var count = this.collection.models.length;				
				var built = 0;
				for (var i in this.collection.models) {							
					var model = this.collection.models[i];
					this.buildRow(i, model, function () {
						built++;						
						if (built == count) {
							if (typeof callback == 'function') {
								callback();
							}
							this.wait(false);
						}
					}.bind(this));
				}
			} else {
				if (typeof callback == 'function') {
					callback();
				}
			}
		}
	
	});
	
});
