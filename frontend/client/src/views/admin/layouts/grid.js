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
	
Espo.define('Views.Admin.Layouts.Grid', 'Views.Admin.Layouts.Base', function (Dep) {		

	return Dep.extend({	
	
		template: 'admin.layouts.grid',
		
		dataAttributes: null,
		
		panels: null,
		
		columnCount: 2,
		
		data: function () {
			return {
				scope: this.scope,
				type: this.type,
				buttons: this.buttons,
				enabledFields: this.enabledFields,
				disabledFields: this.disabledFields,
				panels: this.panels,
				columnCount: this.columnCount
			};
		},
		
		events: _.extend({				
			'click #layout a[data-action="add-panel"]': function () {
				this.addPanel();
				this.makeDraggable();
			},
			'click #layout a[data-action="remove-panel"]': function (e) {
				$(e.target).closest('ul.panels > li').find('ul.cells > li').each(function (i, li) {
					if ($(li).attr('data-name')) {
						$(li).appendTo($('#layout ul.disabled'));
					}						
				});
				$(e.target).closest('ul.panels > li').remove();
			},
			'click #layout a[data-action="add-row"]': function (e) {					
				var tpl = this.unescape($("#layout-row-tpl").html());
				var html = _.template(tpl);					
				$(e.target).closest('ul.panels > li').find('ul.rows').append(html);
				this.makeDraggable();
			},
			'click #layout a[data-action="remove-row"]': function (e) {
				$(e.target).closest('ul.rows > li').find('ul.cells > li').each(function (i, li) {
					if ($(li).attr('data-name')) {
						$(li).appendTo($('#layout ul.disabled'));
					}						
				});
				$(e.target).closest('ul.rows > li').remove();
			},
			'click #layout a[data-action="remove-field"]': function (e) {		
				var el = $(e.target).closest('li');				
				var index = el.index();
				var parent = el.parent();				
			
				el.appendTo($("ul.disabled"));
				
				var empty = $('<li class="empty disabled"></li>');
				
				if (index == 0) {
					parent.prepend(empty);
				} else {
					empty.insertAfter(parent.children(':nth-child(' + index + ')'));
				}
				this.makeDraggable();
			},
			'click #layout a[data-action="edit-panel-label"]': function (e) {
				var el = $(e.target).closest('header').children('label');
				var value = el.text();
				
				var dialog = new Espo.Ui.Dialog({
					modal: true,
					body: '<label>' + this.getLanguage().translate('Panel Name', 'labels', 'admin') + '</label><input type="text" name="label" class="form-control" value="'+value+'">',
					buttons: [
						{
							name: 'ok',
							text: '&nbsp; Ok &nbsp;',
							onClick: function (dialog) {						
								el.text(dialog.$el.find('input[name="label"]').val());
								dialog.close();
							}.bind(this),								
						},
						{
							name: 'cancel',
							text: 'Cancel',
							onClick: function (dialog) {
								dialog.close();
							}
						} 
					]		
				});
				dialog.show();
			}		
		}, Dep.prototype.events),
		
		addPanel: function (data) {
			var tpl = this.unescape($("#layout-panel-tpl").html());
			data = data || {label: 'new panel', rows: [[]]};
							
			data.rows.forEach(function (row) {
				var rest = this.columnCount - row.length;
				for (var i = 0; i < rest; i++) {
					row.push(false);
				}
				for (var i in row) {
					if (row[i] != false) {
						row[i].label = this.getLanguage().translate(row[i].name, 'fields', this.scope);
					}
				
				}
			}.bind(this));
			
			
			var html = _.template(tpl, data);				
			$("#layout .panels").append(html);				
		},			
		
		makeDraggable: function () {			
			$('#layout ul.panels').sortable({distance: 4});
			$('#layout ul.panels').disableSelection();
			
			$('#layout ul.rows').sortable({
				distance: 4,
				connectWith: '.rows',
			});
			$('#layout ul.rows').disableSelection();				
							
			$('#layout ul.cells > li').draggable({revert: 'invalid', revertDuration: 200, zIndex: 10}).css('cursor', 'pointer');	
		
			$('#layout ul.cells > li').droppable().droppable('destroy');				
			
			var self = this;			
			$('#layout ul.cells:not(.disabled) > li').droppable({
				accept: '.cell',
				zIndex: 10,
				drop: function (e, ui) {						
					var index = ui.draggable.index();						
					var parent = ui.draggable.parent();					
					
					if (parent.get(0) == $(this).parent().get(0)) {
						if ($(this).index() < ui.draggable.index()) {
							$(this).before(ui.draggable);
						} else {
							$(this).after(ui.draggable);
						}
					} else {
						ui.draggable.insertAfter($(this));
						
						if (index == 0) {								
							$(this).prependTo(parent);
						} else {
							$(this).insertAfter(parent.children(':nth-child(' + (index) + ')'));
						}
					}
					
					ui.draggable.css({
						top: 0,
						left: 0,
					});						
					
					if ($(this).parent().hasClass('disabled') && !$(this).data('name')) {
						$(this).remove();
					}
					
					self.makeDraggable();
				}
			});
		},
		
		afterRender: function () {
			this.panels.forEach(function (panel) {
				this.addPanel(panel);
			}.bind(this));
			this.makeDraggable();
		},			
		
		fetch: function () {
			var layout = [];
			var self = this;
			$("#layout ul.panels > li").each(function () {
				var o = {
					label: $(this).find('header label').text(),
					rows: []
				};
				$(this).find('ul.rows > li').each(function (i, li) {	
					var row = [];							
					$(li).find('ul.cells > li').each(function (i, li) {	
						var cell = false;
						if (!$(li).hasClass('empty')) {										
							cell = {};
							self.dataAttributes.forEach(function (attr) {
								cell[attr] = $(li).data(attr);
							});
						}
						row.push(cell);
					});
					o.rows.push(row);
				});
				
				layout.push(o);
			});
			
			return layout;		
		},
		
		validate: function (layout) {
			var fieldCount = 0;				
			layout.forEach(function (panel) {
				panel.rows.forEach(function (row) {
					row.forEach(function (cell) {
						if (cell != false) {
							fieldCount++;
						}
					});
				});
			});
			if (fieldCount == 0) {
				alert('Layout cannot be empty.');
				return false;
			}
			return true;
		},	
	});
});


