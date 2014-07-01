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

Espo.define('Views.Fields.LinkMultipleWithRole', 'Views.Fields.LinkMultiple', function (Dep) {

	return Dep.extend({

		type: 'linkMultipleWithRole',
		
		setup: function () {
			Dep.prototype.setup.call(this);			
			
			this.columnsName = this.name + 'Columns';			
			this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});
			
			this.listenTo(this.model, 'change:' + this.columnsName, function () {
				this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {}); 					
			}.bind(this));
			
			this.roleField = this.getMetadata().get('entityDefs.' + this.model.name + '.fields.' + this.name + '.columns.role');			
			this.roleList = this.getMetadata().get('entityDefs.' + this.foreignScope + '.fields.' + this.roleField + '.options');			
		},
		
		getAttributeList: function () {
			var list = Dep.prototype.getAttributeList.call(this);
			list.push(this.name + 'Columns');
			return list;
		},
		
		getValueForDisplay: function () {
			var nameHash = this.nameHash;
			var string = '';
			var names = [];			
			
			for (var id in nameHash) {
				var role = (this.columns[id] || {}).role || '';
				var roleHtml = '';
				if (role != '') {
					roleHtml = '<span class="text-muted small">(' + this.getLanguage().translateOption(role, this.roleField, this.foreignScope) + ')</span>';
				}
				names.push('<a href="#' + this.foreignScope + '/view/' + id + '">' + nameHash[id] + '</a> ' + roleHtml);
			}
			return '<div>' + names.join('</div><div>') + '</div>';
		},
		
		deleteLink: function (id) {	
			this.$el.find('.link-' + id).remove();
					
			var index = this.ids.indexOf(id);
			if (index > -1) {
				this.ids.splice(index, 1);
			}
			delete this.nameHash[id];
			delete this.columns[id];
			this.trigger('change');
		},

		addLink: function (id, name) {
			if (!~this.ids.indexOf(id)) {
				this.ids.push(id);
				this.nameHash[id] = name;
				this.columns[id] = {role: null};
				this.addLinkHtml(id, name);
			}
			this.trigger('change');
		},
		
		afterRender: function () {
			Dep.prototype.afterRender.call(this);

		},
		
		addLinkHtml: function (id, name) {		
			var $conteiner = this.$el.find('.link-container');
			var $el = $('<div class="form-inline">').addClass('link-' + id).addClass('list-group-item');
			
			var nameHtml = '<span class="">' + name + '&nbsp;' + '</div>';
			
			
			var removeHtml = '<a href="javascript:" class="" data-id="' + id + '" data-action="clearLink"><span class="glyphicon glyphicon-remove"></a>';
		
			var $select = $('<select class="role form-control input-sm" data-id="'+id+'">');
			this.roleList.forEach(function (role) {
				var selectedHtml = (role == (this.columns[id] || {}).role) ? 'selected': '';
				option = '<option value="'+role+'" '+selectedHtml+'>' + this.getLanguage().translateOption(role, this.roleField, this.foreignScope) + '</option>';
				$select.append(option);
			}, this);
						
			
			$contentContainer = $('<div class="pull-left" style="display: inline-block;">');
			
			$contentContainer.append(nameHtml);	
			
			$el.append($contentContainer);			
			
			$right = $('<div class="pull-right" style="display: inline-block;">');			
			$right.append($select);
			$right.append(removeHtml);
			
						
			$el.append($right)
			
			$el.append('<br style="clear: both;" />');
			
			$conteiner.append($el);
			
			if (this.mode == 'edit') {
				$select.on('change', function (e) {
					var $target = $(e.currentTarget);
					var value = $target.val();
					var id = $target.data('id');
					this.columns[id] = this.columns[id] || {};
					this.columns[id].role = value;
					this.trigger('change');
				}.bind(this));
			}
			
		},
		
		fetch: function () {
			var data = Dep.prototype.fetch.call(this);
			data[this.columnsName] = Espo.Utils.cloneDeep(this.columns);
			return data;
		},

	});
});


