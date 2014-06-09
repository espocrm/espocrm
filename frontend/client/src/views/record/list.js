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
	
Espo.define('Views.Record.List', 'View', function (Dep) {

	return Dep.extend({

		template: 'record.list',

		/**
		 * @param {String} Type of the list. Can be 'list', 'listSmall'.
		 */
		type: 'list',

		name: 'list',
		
		presentationType: 'table',

		/**
		 * @param {Bool} If true checkboxes will be shown.
		 */
		checkboxes: true,

		/**
		 * @param {Bool} If true clicking on the record link will trigger 'select' event with model passed.
		*/
		selectable: false,

		rowButtons: 'Record.ListButtons.Default',

		scope: null,

		_internalLayoutType: 'list-row',
		
		listContainerEl: '.list > table > tbody',

		events: {
			'click a.link': function (e) {
				if (!this.scope || this.selectable) {
					return;
				}
				e.preventDefault();
				var id = $(e.currentTarget).data('id');
				var model = this.collection.get(id);
				
				this.getRouter().navigate('#' + this.scope + '/view/' + id);
				this.getRouter().dispatch(this.scope, 'view', {
					id: id,
					model: model
				});
							
			},
			'click [data-action="showMore"]': function () {
				this.showMoreRecords();		
			},
			'click a.sort': function (e) {
				var field = $(e.currentTarget).data('name');			
				
				var asc = true;
				if (field === this.collection.sortBy && this.collection.asc) {
					asc = false;
				}
				this.notify('Please wait...');
				this.collection.once('sync', function () {
					this.notify(false);
					this.trigger('sort', {field: field, asc: asc});
				}.bind(this));
				this.collection.sort(field, asc);
				this.deactivate();
			},
			'click .pagination a': function (e) {
				var page = $(e.currentTarget).data('page');
				if ($(e.currentTarget).parent().hasClass('disabled')) {
					return;
				}
				this.notify('Please wait...');
				this.collection.once('sync', function () {
					this.notify(false);
				}.bind(this));

				if (page == 'current') {
					this.collection.fetch();
				} else {
					this.collection[page + 'Page'].call(this.collection);
					this.trigger('paginate');
				}
				this.deactivate();
			},

			'click .record-checkbox': function (e) {
				var target = $(e.currentTarget);
				var id = target.data('id');				
				this._checkRecord(id, e.currentTarget.checked, target);
			},
			'click .selectAll': function (e) {
				this.checkedList = [];

				if (e.currentTarget.checked) {
					this.$el.find('input.record-checkbox').prop('checked', true);
					this.$el.find('.actions-button').removeAttr('disabled');
					_.each(this.collection.models, function (model) {
						this.checkedList.push(model.id);
					}.bind(this));

					this.$el.find('.list > table tbody tr').addClass('active');
				} else {
					this.$el.find('input.record-checkbox').prop('checked', false);
					this.$el.find('.actions-button').attr('disabled', true);
					this.$el.find('.list > table tbody tr').removeClass('active');
				}
			},
			'click [data-action="quickEdit"]': function (e) {				
				var id = $(e.currentTarget).data('id');				
				this.quickEdit(id);				

			},
			'click [data-action="quickRemove"]': function (e) {
				var id = $(e.currentTarget).data('id');
				this.quickRemove(id);
			},
		},

		actions: [
			{
				name: 'delete',
				label: 'Delete',
				action: function (e) {
					if (!this.getAcl().check(this.scope, 'delete')) {
						this.notify('Access denied', 'error');
						return false;
					}

					var count = this.checkedList.length;
					var deletedCount = 0;

					var self = this;

					if (confirm(this.translate('Are you sure?'))) {
						// TODO mass delete
						this.notify('Deleting...');
						for (var i in this.checkedList) {
							var id = this.checkedList[i];
							var model = this.collection.get(id);

							this.collection.remove(model);
							this.$el.find('tr[data-id="'+id+'"]').remove();

							model.once('sync', function (model) {
								deletedCount ++;
								if (deletedCount == count) {
									Espo.Ui.notify(false);
								}
							}, this);
							model.destroy({
								error: function () {
									self.notify('Error occured', 'error');
								},
							});
						}
						this.checkedList = [];
					}
				},
			},
			{
				name: 'merge',
				label: 'Merge',
				action: function (e) {
					if (!this.getAcl().check(this.scope, 'edit')) {
						this.notify('Access denied', 'error');
						return false;
					}

					if (this.checkedList.length < 2) {
						this.notify('Select 2 or more records', 'error');
						return;
					}
					if (this.checkedList.length > 4) {
						this.notify('Select not more than 4 records', 'error');
						return;
					}
					this.checkedList.sort();
					var url = '#' + this.scope + '/merge/ids=' + this.checkedList.join(',');
					this.getRouter().navigate(url, {trigger: true});
				}
			},
			{
				name: 'massUpdate',
				label: 'Mass Update',
				action: function (e) {
					if (!this.getAcl().check(this.scope, 'edit')) {
						this.notify('Access denied', 'error');
						return false;
					}

					this.notify('Loading...');
					var ids = this.checkedList;
					this.createView('massUpdate', 'Modals.MassUpdate', {
						scope: this.scope,
						ids: ids,
						where: this.collection.where
					}, function (view) {
						view.render();
						view.notify(false);
						view.once('after:update', function () {
							view.close();
							this.listenToOnce(this.collection, 'sync', function () {
								ids.forEach(function (id) {
									this.checkRecord(id);
								}, this);
							}.bind(this));
							this.collection.fetch();
						}, this);
					}.bind(this));
				},
			},
			{
				name: 'export',
				label: 'Export',
				action: function (e) {
					var ids = this.checkedList;
					var where = this.collection.where;
					
					$.ajax({
						url: this.scope + '/action/export',
						type: 'GET',
						data: {
							ids: ids || null,
							where: (ids.length == 0) ? where : null,
						},
						success: function (data) {
							if ('id' in data) {
								window.location = '?entryPoint=download&id=' + data.id;
							}
						},
					});
				},
			}
		],

		/**
		 * @param {string} or {bool} ['both', 'top', 'bottom', false, true] Where to display paginations.
		 */
		pagination: false,

		/**
		 * @param {bool} To dispaly table header with column names.
		 */
		header: true,
		
		showMore: true,

		/**
		 * @param {array} Columns layout. Will be convered in 'Bull' typed layout for a fields rendering.
		 */
		listLayout: null,

		_internalLayout: null,

		checkedList: null,

		data: function () {
			var paginationTop = this.pagination === 'both' || this.pagination === true || this.pagination === 'top';
			var paginationBottom = this.pagination === 'both' || this.pagination === true || this.pagination === 'bottom';
			return {
				scope: this.scope,
				header: this.header,
				headerDefs: this._getHeaderDefs(),
				paginationEnabled: this.pagination,
				paginationTop: paginationTop,
				paginationBottom: paginationBottom,
				showMoreActive: this.collection.total > this.collection.length,
				showMoreEnabled: this.showMore,
				checkboxes: this.checkboxes,
				actions: this._getActions(),
				rows: this.rows,
				topBar: paginationTop || this.checkboxes,
				bottomBar: paginationBottom,
			};
		},

		init: function () {
			this.listLayout = this.options.listLayout || this.listLayout;
			this.type = this.options.type || this.type;
			this.header = _.isUndefined(this.options.header) ? this.header : this.options.header;
			this.pagination = _.isUndefined(this.options.pagination) ? this.pagination : this.options.pagination;
			this.checkboxes = _.isUndefined(this.options.checkboxes) ? this.checkboxes : this.options.checkboxes;
			this.selectable = _.isUndefined(this.options.selectable) ? this.selectable : this.options.selectable;
			this.rowButtons = _.isUndefined(this.options.rowButtons) ? this.rowButtons : this.options.rowButtons;
			this.showMore = _.isUndefined(this.options.showMore) ? this.showMore : this.options.showMore; 
		},

		deactivate: function () {
			if (this.$el) {
				this.$el.find(".pagination li").addClass('disabled');
				this.$el.find("a.sort").addClass('disabled');
			}
		},

		setup: function () {
			if (typeof this.collection === 'undefined') {
				throw new Error('Collection has not been injected into Record.List view.');
			}

			this.scope = this.collection.name || null;
			this.events = _.clone(this.events);

			if (this.selectable) {
				this.events['click .list a.link'] = function (e) {
					e.preventDefault();
					var id = $(e.target).data('id');
					if (id) {
						var model = this.collection.get(id);
						if (this.checkboxes) {
							var list = [];
							list.push(model);
							this.trigger('select', list);
						} else {
							this.trigger('select', model);
						}
					}
				};
			}

			if (this.options.actions === false) {
				this.actions = [];
			}

			if (this.checkboxes) {
				this.actions.forEach(function (item) {
					this.events['click .actions a[data-action="' + item.name + '"]'] = function (e) {
						item.action.call(this, e);
					}.bind(this);
				}.bind(this));
			}

			this.listenTo(this.collection, 'sync', function () {
				if (this.noRebuild) {					
					this.noRebuild = null;
					return;
				}
				this.buildRows(function () {
					this.render();
				}.bind(this));
			}, this);

			this.checkedList = [];
			this.buildRows();
		},

		_getActions: function () {
			if (this.checkboxes) {
				return this.actions;
			}
		},

		_loadListLayout: function (callback) {
			this._helper.layoutManager.get(this.collection.name, this.type, function (listLayout) {
				callback(listLayout);
			});
		},

		_getHeaderDefs: function () {
			var defs = [];

			for (var i in this.listLayout) {
				var item = {
					name: this.listLayout[i].name,
					sortable: ('sortable' in this.listLayout[i]) ? this.listLayout[i].sortable : true,
					width: ('width' in this.listLayout[i]) ? this.listLayout[i].width : false
				};
				if (item.sortable) {
					item.sorted = this.collection.sortBy === this.listLayout[i].name;
					if (item.sorted) {
						item.asc = this.collection.asc;
					}
				}
				defs.push(item);
			};
			if (this.rowButtons) {
				defs.push({
					width: '7%',
				});
			}
			return defs;
		},

		_convertLayout: function (listLayout, model) {
			model = model || this.collection.model.prototype;
			
			var layout = [];

			if (this.checkboxes) {
				layout.push({
					name: 'checkbox',
					template: 'record.list-checkbox'
				});
			}

			for (var i in listLayout) {
				var col = listLayout[i];
				var type = col.type || model.getFieldType(col.name) || 'base';				
				var item = {
					name: col.name,
					view: col.view || model.getFieldParam(col.name, 'view') || this.getFieldManager().getViewName(type),					
					options: {
						defs: {
							name: col.name,
							params: col.params || {}
						},
						mode: 'list'
					}
				};
				if (col.link) {
					item.options.mode = 'listLink';
				}
				if ('sortable' in col) {
					item.options.defs.sortable = col.sortable;
				}
				layout.push(item);
			}
			if (this.rowButtons) {
				layout.push(this.getRowButtonsDefs());
			}
			return layout;
		},
		
		checkRecord: function (id) {
			var $target = this.$el.find('.record-checkbox[data-id="' + id + '"]');			
			$target.get(0).checked = true;
			this._checkRecord(id, true, $target);
		},
		
		_checkRecord: function (id, checked, target) {
				var index = this.checkedList.indexOf(id);

				if (checked) {
					if (index == -1) {
						this.checkedList.push(id);
					}
					target.closest('tr').addClass('active');
				} else {
					if (index != -1) {
						this.checkedList.splice(index, 1);
					}
					target.closest('tr').removeClass('active');
				}

				if (this.checkedList.length) {
					this.$el.find('.actions-button').removeAttr('disabled');
				} else {
					this.$el.find('.actions-button').attr('disabled', true);
				}

				if (this.checkedList.length == this.collection.models.length) {
					this.$el.find('.select-all').prop('checked', true);
				} else {
					this.$el.find('.select-all').prop('checked', false);
				}
		},

		getRowButtonsDefs: function () {
			return {
				name: 'buttons',
				view: this.rowButtons,
				options: {
					defs: {
						params: {
							width: '7%'
						}
					},
				},
			};
		},

		/**
		 * Returns checked models.
		 * @return {Array} Array of models
		 */
		getSelected: function () {
			var list = [];
			this.$el.find('input.record-checkbox:checked').each(function (i, el) {
				var id = $(el).data('id');
				var model = this.collection.get(id);
				list.push(model);
			}.bind(this));
			return list;
		},
		
		getInternalLayoutForModel: function (callback, model) {
			var scope = model.name;
			if (this._internalLayout == null) {
				this._internalLayout = {};
			}
			if (!(scope in this._internalLayout)) {
				this._internalLayout[scope] = this._convertLayout(this.listLayout[scope], model);
			}
			callback(this._internalLayout[scope]);
		},

		getInternalLayout: function (callback, model) {
			if (this.scope == null) {
				if (!model) {
					callback(null);
					return;
				} else {
					this.getInternalLayoutForModel(callback, model); 
					return;					
				}
			}				
			if (this._internalLayout !== null) {
				callback(this._internalLayout);
				return;
			}				
			if (this.listLayout !== null) {
				this._internalLayout = this._convertLayout(this.listLayout);
				callback(this._internalLayout);
				return;
			}
			this._loadListLayout(function (listLayout) {
				this.listLayout = listLayout;
				this._internalLayout = this._convertLayout(listLayout);
				callback(this._internalLayout);
				return;
			}.bind(this));
		},
		
		buildRow: function (i, model, callback) {
			var key = 'row-' + model.id;
			
			this.rows.push(key);
			this.getInternalLayout(function (internalLayout) {
				if (this.presentationType == 'table' && Object.prototype.toString.call(internalLayout) === '[object Array]') {
					internalLayout.forEach(function (item) {
						item.el = this.options.el + ' tr[data-id="' + model.id + '"] td.cell-' + item.name;
					}, this);
				}
 
				this.createView(key, 'Base', {
					model: model,
					acl: {
						edit: this.getAcl().checkModel(model, 'edit')
					},
					optionsToPass: ['acl'],
					noCache: true,
					_layout: {
						type: this._internalLayoutType,
						layout: internalLayout,
					},
					name: this.type + '-' + model.name
				}, callback);
			}.bind(this), model);
		},

		buildRows: function (callback) {
			this.checkedList = [];
			this.rows = [];
			

			if (this.collection.length > 0) {
							
				var i = 0;
				var c = !this.pagination ? 1 : 2;
				var func = function () {
					i++;
					if (i == c) {
						if (typeof callback == 'function') {
							callback();
						}
					}
				}

				this.wait(true);
				this.getInternalLayout(function (internalLayout) {						
					var count = this.collection.models.length;
					var built = 0;
					for (var i in this.collection.models) {							
						var model = this.collection.models[i];
						this.buildRow(i, model, function () {
							built++;								
							if (built == count) {
								func();
								this.wait(false);
							}
						}.bind(this));
					}					
				}.bind(this));

				if (this.pagination) {
					this.createView('pagination', 'Record.ListPagination', {
						collection: this.collection
					}, func);
				}
			} else {
				if (typeof callback == 'function') {
					callback();
				}
			}
		},
		
		showMoreRecords: function () {
			var collection = this.collection;			
					
			var $showMore = this.$el.find('.show-more');
			var $list = this.$el.find(this.listContainerEl);
				
			$showMore.children('a').addClass('disabled');	
			this.notify('Loading...');
				
			var final = function () {
				$showMore.parent().append($showMore);
				if (collection.total > collection.length) {
					$showMore.removeClass('hide');
				}
				$showMore.children('a').removeClass('disabled');
				this.notify(false);
			}.bind(this);
			
			var initialCount = collection.length;
				
			var success = function () {
				this.notify(false);
				$showMore.addClass('hide');
				
				var temp = collection.models[initialCount - 1];				
				
				var rowCount = collection.length - initialCount;
				var rowsReady = 0;
				for (var i = initialCount; i < collection.length; i++) {					
					var model = collection.at(i);
					
					this.buildRow(i, model, function (view) {			
						view.getHtml(function (html) {
							$list.append(html);								
							rowsReady++;
							if (rowsReady == rowCount) {			
								final();
							}
							if (view.options.el) {
								view.setElement(view.options.el);
							}													
						}.bind(this));
					});						
				}	
				this.noRebuild = true;
			}.bind(this);
			
			collection.fetch({
				success: success,
				remove: false,
				more: true,
			});
		},
		
		quickEdit: function (id) {
			this.notify('Loading...');
			this.createView('quickEdit', 'Modals.Edit', {
				scope: this.scope,
				id: id
			}, function (view) {
				view.once('after:render', function () {
					Espo.Ui.notify(false);
				});
				view.render();
				view.once('after:save', function () {
					this.collection.get(id).fetch();
				}, this);
			}.bind(this));
		},
		
		quickRemove: function (id) {
			var model = this.collection.get(id);				
			if (!this.getAcl().checkModel(model, 'delete')) {
				this.notify('Access denied', 'error');
				return false;
			}
			var self = this;
			if (confirm(this.translate('Are you sure?'))) {
				this.collection.remove(model);
				this.notify('Removing...');			
				model.destroy({
					success: function () {						
						self.notify('Removed', 'success');
						self.$el.find('tr[data-id="' + id + '"]').remove();
						self.collection.total--;
					},
					error: function () {
						self.notify('Error occured', 'error');
					},
				});
			}
		}
	});
});

