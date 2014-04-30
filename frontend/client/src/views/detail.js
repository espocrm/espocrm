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

Espo.define('Views.Detail', 'Views.Main', function (Dep) {

	return Dep.extend({

		template: 'detail',

		el: '#main',

		scope: null,

		name: 'Detail',
		
		optionsToPass: [],

		views: {
			header: {
				selector: '> .page-header',
				view: 'Header'
			},
			body: {
				view: 'Record.Detail',
				selector: '> .body',
			}
		},
		
		setup: function () {
			Dep.prototype.setup.call(this);
			
			if (this.model.has('isFollowed')) {
				if (this.getMetadata().get('scopes.' + this.scope + '.stream')) {
					if (this.model.get('isFollowed')) {
						this.menu.buttons.unshift({
							name: 'unfollow',
							label: 'Followed',
							style: 'success',
							action: 'unfollow'
						});
					} else {
						this.menu.buttons.unshift({
							name: 'follow',
							label: 'Follow',
							style: 'default',
							icon: 'glyphicon glyphicon-share-alt',
							action: 'follow'
						});
					}
				}
			} else {
				this.once('after:render', function () {
					var proceed = function () {
						if (this.model.has('isFollowed')) {
							if (this.model.get('isFollowed')) {
								this.addUnfollowButton();
							} else {
								this.addFollowButton();
							}					
						}
					}.bind(this);
					
					if (this.model.has('isFollowed')) {
						proceed();
					} else {
						this.listenToOnce(this.model, 'sync', function () {
							proceed();
						}.bind(this));
					}

				}, this);
			}			
		},
		
		addFollowButton: function () {
			$el = $('<button>').addClass('btn btn-default action')
			                   .attr('data-action', 'follow')
			                   .html('<span class="glyphicon glyphicon-share-alt"></span> ' + this.translate('Follow'));			
			$("div.header-buttons").prepend($el);
		},
		
		addUnfollowButton: function () {
			$el = $('<button>').addClass('btn btn-default action btn-success')
			                   .attr('data-action', 'unfollow')
			                   .html(this.translate('Followed'));			
			$("div.header-buttons").prepend($el);
		},
		
		actionFollow: function () {
			$el = this.$el.find('button[data-action="follow"]');
			$el.addClass('disabled');
			$.ajax({
				url: this.model.name + '/' + this.model.id + '/subscription',
				type: 'PUT',
				success: function () {
					$el.remove();
					this.addUnfollowButton();
				}.bind(this)
			});			
		},
		
		actionUnfollow: function () {
			$el = this.$el.find('button[data-action="unfollow"]');
			$el.addClass('disabled');
			$.ajax({
				url: this.model.name + '/' + this.model.id + '/subscription',
				type: 'DELETE',
				success: function () {					
					$el.remove();
					this.addFollowButton();
				}.bind(this)
			});
			
		},

		getHeader: function () {
			var html = '<a href="#' + this.model.name + '">' + this.getLanguage().translate(this.model.name, 'scopeNamesPlural') + '</a>';
			html += ' &raquo ' + this.model.get('name');
			return html;
		},

		updatePageTitle: function () {
			this.setPageTitle(this.model.get('name'));
		},
		
		updateRelationshipPanel: function (name) {
			var bottom = this.getView('body').getView('bottom');
			if (bottom) {
				var rel = bottom.getView(name);
				if (rel) {
					rel.collection.fetch();
				}
			}				
		},
		
		relatedAttributeList: {},

		actionCreateRelated: function (data) {
			var self = this;
			var link = data.link;
			var scope = this.model.defs['links'][link].entity;
			var foreignLink = this.model.defs['links'][link].foreign;
			
			var attributes = {};
			
			Object.keys(this.relatedAttributeMap[link] || {}).forEach(function (attr) {
				attributes[this.relatedAttributeMap[link][attr]] = this.model.get(attr);
			}, this);

			this.notify('Loading...');
			this.getModelFactory().create(scope, function (model) {
				this.createView('quickCreate', 'Modals.Edit', {
					scope: scope,
					relate: {
						model: this.model,
						link: foreignLink,
					},
					attributes: attributes,
				}, function (view) {
					view.render();
					view.notify(false);
					view.once('after:save', function () {
						self.updateRelationshipPanel(link);
					});
				});
			}.bind(this));
		},

		actionSelectRelated: function (data) {
			var link = data.link;
			var scope = this.model.defs['links'][link].entity;
			
			var self = this;

			this.notify('Loading...');
			this.createView('dialog', 'Modals.SelectRecords', {
				scope: scope,
				multiple: true,
				createButton: false,
			}, function (dialog) {
				dialog.render();
				this.notify(false);
				dialog.once('select', function (selectObj) {				
					var data = {};									
					if (Object.prototype.toString.call(selectObj) === '[object Array]') {
						var ids = [];
						selectObj.forEach(function (model) {
							ids.push(model.id);
						});
						data.ids = ids;
					} else {
						data.id = selectObj.id;
					}
					$.ajax({
						url: self.scope + '/' + self.model.id + '/' + link, 
						type: 'POST',						
						data: JSON.stringify(data),
						success: function () {
							self.notify('Linked', 'success');
							self.updateRelationshipPanel(link);
						},
						error: function () {
							self.notify('Error occurred', 'error');
						},
					});
				}.bind(this));
			}.bind(this));
		}
	});
});

