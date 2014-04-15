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

Espo.define('Views.Fields.LinkMultiple', 'Views.Fields.Base', function (Dep) {

	return Dep.extend({

		type: 'linkMultiple',

		listTemplate: 'fields.link-multiple.detail',

		detailTemplate: 'fields.link-multiple.detail',

		editTemplate: 'fields.link-multiple.edit',
		
		searchTemplate: 'fields.link-multiple.search',

		nameHashName: null,

		idsName: null,

		nameHash: null,

		foreignScope: null,

		data: function () {
			var ids = this.model.get(this.idsName);

			return _.extend({
				idValues: this.model.get(this.idsName),
				idValuesString: ids ? ids.join(',') : '',
				nameHash: this.model.get(this.nameHashName),
				foreignScope: this.foreignScope,
			}, Dep.prototype.data.call(this));
		},		

		setup: function () {
			this.nameHashName = this.name + 'Names';
			this.idsName = this.name + 'Ids';
			this.foreignScope = this.model.defs.links[this.name].entity;

			var self = this;
			
			this.nameHash = _.clone(this.model.get(this.nameHashName)) || {};			
			if (this.mode == 'search') {
				this.nameHash = _.clone(this.searchParams.nameHash) || {};
			}			
			
			this.listenTo(this.model, 'change:' + this.idsName, function () {
				this.nameHash = _.clone(this.model.get(this.nameHashName)) || {};			
			}.bind(this));
			

			if (this.mode != 'list') {
				this.addActionHandler('selectLink', function () {
					self.notify('Loading...');
					this.createView('dialog', 'SelectModal', {scope: this.foreignScope}, function (dialog) {
						dialog.render();
						self.notify(false);
						dialog.once('select', function (model) {
							self.addLink(model.id, model.get('name'));
						});
					});
				});

				this.events['click a[data-action="clearLink"]'] = function (e) {
					var id = $(e.currentTarget).data('id').toString();
					this.deleteLink(id);
				};
			}
		},
		
		afterRender: function () {
	
			if (this.mode == 'edit' || this.mode == 'search') {			
				this.$element = this.$el.find('input.main-element');				
				
				this.$element.autocomplete({
					serviceUrl: function (q) {
						return this.foreignScope + '?orderBy=name&limit=7';
					}.bind(this),
					minChars: 1,
					paramName: 'q',
			       	formatResult: function (suggestion) {
			        	return suggestion.name;
			        },
			        transformResult: function (response) {
			        	var response = JSON.parse(response);	        	
			        	var list = [];			        	
			        	response.list.forEach(function(item) {
			        		list.push({
			        			id: item.id,
			        			name: item.name,
			        			data: item.id,
			        			value: item.name
					        });
			        	}, this);
			        	return {
			        		suggestions: list
			        	};			        	
			        }.bind(this),
					onSelect: function (s) {
						this.addLink(s.id, s.name);
						this.$element.val('');					
					}.bind(this)
				});
				
				var $element = this.$element;
				this.once('render', function () {
					$element.autocomplete('dispose');
				}, this);			
				
				this.once('remove', function () {
					$element.autocomplete('dispose');
				}, this);
			}
		},

		deleteLink: function (id) {
			this.$el.find('.link-' + id).remove();
			var idsEl = this.$el.find('.ids');
			var ids = idsEl.val().split(',');
			
			var index = ids.indexOf(id);
			if (index > -1) {
				ids.splice(index, 1);
			}
			idsEl.val(ids.join(','));				 
			delete this.nameHash[id];
			this.trigger('change');
		},

		addLink: function (id, name) {
			var idsEl = this.$el.find('.ids');
			var value = idsEl.val();
			var ids = [];
			if (value != '') {
				ids = value.split(',');
			}
			
			if (ids.indexOf(id) == -1) {
				this.nameHash[id] = name;
				ids.push(id);
				idsEl.val(ids.join(','));
				var conteiner = this.$el.find('.link-container');
				var el = $('<div />').addClass('link-' + id).addClass('list-group-item');
				el.html(name);
				el.append('<a href="javascript:" class="pull-right" data-id="'+id+'" data-action="clearLink"><span class="glyphicon glyphicon-remove"></a>');
				conteiner.append(el);
			}
			this.trigger('change');
		},

		getValueForDisplay: function () {
			var nameHash = this.nameHash;
			var string = '';
			var names = [];
			for (var id in nameHash) {
				names.push('<a href="#' + this.foreignScope + '/view/' + id + '">' + nameHash[id] + '</a>');
			}
			return names.join(', ');
		},

		validateRequired: function () {
			if (this.model.isRequired(this.name)) {
				if (this.model.get(this.idsName).length == 0) {
					var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
					this.showValidationMessage(msg);
					return true;
				}
			}
		},

		fetch: function () {
			var data = {};
			var value = this.$el.find('[name="' + this.idsName + '"]').val();
			if (value != '') {
				data[this.idsName] = value.split(',').sort();
			} else {
				data[this.idsName] = [];
			}
			data[this.nameHashName] = this.nameHash;

			return data;
		},
		
		fetchSearch: function () {
			var values = [];
			var value = this.$el.find('[name="' + this.idsName + '"]').val();
			
			if (!value) {
				return false;
			}
			
			if (value != '') {
				values = value.split(',');
			}
			
			var data = {
				type: 'linkedWith',
				value: values,
				nameHash: this.nameHash 
			};
			return data;
		},

	});
});


