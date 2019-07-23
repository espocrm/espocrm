/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/admin/layouts/grid', 'views/admin/layouts/base', function (Dep) {

    return Dep.extend({

        template: 'admin/layouts/grid',

        dataAttributeList: null,

        panels: null,

        panelDataAttributeList: ['panelName', 'style'],

        panelDataAttributesDefs: {},

        removeColClasses: function(index, className) {
            return (className.match (/(^|\s)col-\S+/g) || []).join(' ');
        },

        data: function () {
            var data = {
                scope: this.scope,
                type: this.type,
                buttonList: this.buttonList,
                enabledFields: this.enabledFields,
                disabledFields: this.disabledFields,
                panels: this.panels,
                columnCount: this.columnCount,
                panelDataList: this.getPanelDataList()
            };
            return data;
        },

        emptyCellTemplate:
                            '<li class="draggable"><div class="empty disabled cell">' +
                            '<a href="javascript:" data-action="minusCell" class="remove-field pull-right"><i class="fas fa-minus"></i></a>' +
                            '</div></li>',

        events: _.extend({
            'click #layout a[data-action="switchPanelMode"]': function (e) {
                var number = $(e.currentTarget).data('number').toString();
                var panel = $(e.currentTarget).closest('ul.panels > li.panel');

                var panelData = this.panelsData[number];
                panelData['mode'] = panelData['mode'] == 'row'
                                    ? 'column' : 'row';

                this.updatePanelMode(panel, panelData['mode']);
            },
            'click #layout a[data-action="addPanel"]': function () {
                this.addPanel();
                this.makeDraggable();
            },
            'click #layout a[data-action="removePanel"]': function (e) {
                $(e.target).closest('ul.panels > li').find('ul.cells > li').each(function (i, li) {
                    if ($(li).attr('data-name')) {
                        $(li).appendTo($('#layout ul.disabled'));
                        $(li).removeClass(this.removeColClasses);
                    }
                });
                $(e.target).closest('ul.panels > li').remove();

                var number = $(e.currentTarget).data('number');
                this.clearView('panels-' + number);

                var index = -1;
                this.panels.forEach(function (item, i) {
                    if (item.number === number) {
                        index = i;
                    }
                }, this);

                if (~index) {
                    this.panels.splice(index, 1);
                }

            },
            'click #layout a[data-action="addRow"]': function (e) {
                var tpl = this.unescape($("#layout-row-tpl").html());
                var html = _.template(tpl);
                $(e.target).closest('ul.panels > li').find('ul.rows').append(html);
                var panel = $(e.target).closest('li.panel');
                var id = panel.attr("data-number").toString();
                var mode = panel.attr("data-mode");
                this.updatePanelMode(panel, mode);
                this.makeDraggable();
            },
            'click #layout a[data-action="removeRow"]': function (e) {
                $(e.target).closest('ul.rows > li').find('ul.cells > li').each(function (i, li) {
                    if ($(li).attr('data-name')) {
                        $(li).appendTo($('#layout ul.disabled'));
                        $(li).removeClass(this.removeColClasses);
                    }
                });
                var panel = $(e.target).closest('li.panel');
                var id = panel.attr("data-number").toString();
                var mode = panel.attr("data-mode");
                $(e.target).closest('ul.rows > li').remove();
                this.updatePanelMode(panel, mode);
            },
            'click #layout a[data-action="addCell"]': function (e) {
                var empty = $(this.emptyCellTemplate);
                empty.insertBefore($(e.target).closest('ul.cells .add-cell'));
                var panel = $(e.target).closest('li.panel');
                var id = panel.attr("data-number").toString();
                var mode = panel.attr("data-mode");
                this.updatePanelMode(panel, mode);
                this.makeDraggable();
            },
            'click #layout a[data-action="removeField"]': function (e) {
                var el = $(e.target).closest('li');
                var index = el.index();
                var parent = el.parent();

                el.appendTo($('ul.disabled'));
                el.removeClass(this.removeColClasses);

                var empty = $(this.emptyCellTemplate);
                if (index == 0) {
                    parent.prepend(empty);
                } else {
                    empty.insertAfter(parent.children(':nth-child(' + index + ')'));
                }

                var panel = parent.closest('li.panel');
                var id = panel.attr("data-number").toString();
                var mode = panel.attr("data-mode");
                this.updatePanelMode(panel, mode);
                this.makeDraggable();
            },
            'click #layout a[data-action="minusCell"]': function (e) {
                $li = $(e.currentTarget).closest('li');
                $ul = $li.parent();
                $li.remove();
                var panel = $ul.closest('li.panel');
                var id = panel.attr("data-number").toString();
                var mode = panel.attr("data-mode");
                this.updatePanelMode(panel, mode);
            },
            'click #layout a[data-action="edit-panel-label"]': function (e) {
                var $header = $(e.target).closest('header');
                var $label = $header.children('label');
                var panelName = $label.text();

                var id = $header.closest('li').data('number').toString();

                var attributes = {
                    panelName: panelName
                };

                this.panelDataAttributeList.forEach(function (item) {
                    if (item === 'panelName') return;
                    attributes[item] = this.panelsData[id][item];

                }, this);

                var panel = $(e.target).closest('li.panel');
                var self = this;

                var attributeList = this.panelDataAttributeList;
                var attributeDefs = this.panelDataAttributesDefs;

                this.createView('dialog', 'views/admin/layouts/modals/panel-attributes', {
                    attributeList: attributeList,
                    attributeDefs: attributeDefs,
                    attributes: attributes
                }, function (view) {
                    view.render();
                    this.listenTo(view, 'after:save', function (attributes) {
                        $label.text(attributes.panelName);
                        $label.attr('data-is-custom', 'true');
                        this.panelDataAttributeList.forEach(function (item) {
                            if (item === 'panelName') return;
                            this.panelsData[id][item] = attributes[item];
                        }, this);
                        self.updatePanelMode(panel, this.panelsData[id]['mode']);
                        view.close();
                    }, this);
                }, this);
            }
        }, Dep.prototype.events),
        updatePanelMode: function(panel, mode) {
              panel.find('ul.rows > li').removeClass(this.removeColClasses);
              panel.find('ul.cells > li').removeClass(this.removeColClasses);
              switch(mode) {
                  case 'row':
                      var rows = panel.find('ul.rows > li');
                      rows.each(function(i, row) {
                          var cells = $(row).find('ul.cells > li');
                          cells.addClass('col-md-' + parseInt(12 / cells.length));
                      });
                      panel.find('a.switch-panel-mode').addClass('fa-rotate-90');
                      break;
                  case 'column':
                      var columns = panel.find('ul.rows > li');
                      columns.addClass('col-md-' + parseInt(12 / columns.length));
                      panel.find('a.switch-panel-mode').removeClass('fa-rotate-90');
                      break;
              }
              panel.attr("data-mode", mode);
        },
        updatePanelModes() {
            $("#layout ul.panels > li").each(function (i, el) {
                var panel = $(el);
                var mode = panel.attr('data-mode');
                this.updatePanelMode(panel, mode ? mode : 'row');
            }.bind(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.panelDataAttributeList.push('mode');

            this.panelDataAttributesDefs.mode = {
                type: 'enum',
                options: ['row', 'column'],
                translation: 'LayoutManager.options.mode'
            };
            this.panelsData = {};
        },

        addPanel: function () {
            this.lastPanelNumber ++;

            var number = this.lastPanelNumber;
            var data = {
                customLabel: this.translate('New panel', 'labels', 'LayoutManager'),
                rows: [[]],
                number: number
            };
            this.panels.push(data);

            this.panelsData[number.toString()] = {};

            var $li = $('<li class="panel-layout"></li>');
            $li.attr('data-number', number);
            this.$el.find('ul.panels').append($li);

            this.createPanelView(data, true, function (view) {
                view.render();
            }, this);
        },

        getPanelDataList: function () {
            var panelDataList = [];

            this.panels.forEach(function (item) {
                var o = {};
                o.viewKey = 'panel-' + item.number;
                o.number = item.number;
                o.mode = item.mode;
                panelDataList.push(o);
            }, this);

            return panelDataList;
        },

        cancel: function () {
            this.loadLayout(function () {
                var countLoaded = 0;
                this.setupPanels(function () {
                    countLoaded ++;
                    if (countLoaded === this.panels.length) {
                        this.reRender();
                    }
                }.bind(this));
            }.bind(this));
        },

        setupPanels: function (callback) {
            this.lastPanelNumber = -1;

            this.panels = Espo.Utils.cloneDeep(this.panels);

            this.panels.forEach(function (panel, i) {
                panel.number = i;
                this.lastPanelNumber ++;
                this.createPanelView(panel, false, callback);
                this.panelsData[i.toString()] = panel;
            }, this);
        },

        createPanelView: function (data, empty, callback) {
            data.label = data.label || '';

            data.isCustomLabel = false;
            if (data.customLabel) {
                data.label = data.customLabel;
                data.isCustomLabel = true;
            } else {
                data.label = this.translate(data.label, 'labels', this.scope);
            }

            data.style = data.style || null;

            data.rows.forEach(function (row) {
                var rest = this.columnCount - row.length;

                if (empty) {
                    for (var i = 0; i < rest; i++) {
                        row.push(false);
                    }
                }
                for (var i in row) {
                    if (row[i] != false) {
                        row[i].label = this.getLanguage().translate(row[i].name, 'fields', this.scope);
                        if ('customLabel' in row[i]) {
                            row[i].customLabel = row[i].customLabel;
                            row[i].hasCustomLabel = true;
                        }
                    }
                }
            }, this);

            this.createView('panel-' + data.number, 'view', {
                el: this.getSelector() + ' li.panel-layout[data-number="'+data.number+'"]',
                template: 'admin/layouts/grid-panel',
                data: function () {
                    var o = Espo.Utils.clone(data);
                    o.dataAttributeList = [];
                    this.panelDataAttributeList.forEach(function (item) {
                        if (item === 'panelName') return;
                        o.dataAttributeList.push(item);
                    }, this);
                    return o;
                }.bind(this)
            }, callback);
        },

        makeDraggable: function () {
            $('#layout ul.panels').sortable({distance: 4});
            $('#layout ul.panels').disableSelection();

            $('#layout ul.rows').sortable({
                distance: 4,
                connectWith: '.rows',
                cursorAt: { top: 20, left: 20 },
                stop: function() {
                    this.updatePanelModes();
                }.bind(this),
                over: function( event, ui ) {
                    var placeholder = $(ui.placeholder);
                    var targetPanel = placeholder.closest('li.panel');
                    var targetMode = targetPanel.attr('data-mode');
                    placeholder.css('width', '');
                    this.updatePanelMode(targetPanel, targetMode);
                }.bind(this)
            });
            $('#layout ul.rows').disableSelection();

            $('#layout ul.cells > li.draggable').draggable({
                appendTo: '#layout',
                helper: 'clone',
                revert: 'invalid',
                revertDuration: 200,
                zIndex: 10,
                scroll: false,
                start: function(event, ui) {
                    $(ui.helper).width($(event.target).width());
                }
            }).css('cursor', 'pointer');

            $('#layout ul.cells > li').droppable().droppable('destroy');

            var self = this;
            $('#layout ul.cells:not(.disabled) > li').droppable({
                accept: '.draggable',
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

                    var $target = $(this);
                    var $draggable = $(ui.draggable);

                    ui.draggable.css({
                        top: 0,
                        left: 0,
                    });

                    if ($(this).parent().hasClass('disabled') && !$(this).data('name')) {
                        $(this).remove();
                    }

                    self.makeDraggable();
                    self.updatePanelModes();
                }
            });
        },

        afterRender: function () {
            this.makeDraggable();
            this.updatePanelModes();
        },

        fetch: function () {
            var layout = [];
            var self = this;
            $("#layout ul.panels > li").each(function (i, el) {
                var $label = $(el).find('header label');

                var id = $(el).data('number').toString();

                var o = {
                    rows: []
                };

                this.panelDataAttributeList.forEach(function (item) {
                    if (item === 'panelName') return;
                    o[item] = this.panelsData[id][item];
                }, this);

                o.style = o.style || 'default';

                var name = $(el).find('header').data('name');
                if (name) {
                    o.name = name;
                }
                if ($label.attr('data-is-custom')) {
                    o.customLabel = $label.text();
                } else {
                    o.label = $label.text();
                }
                $(el).find('ul.rows > li').each(function (i, li) {
                    var row = [];
                    $(li).find('ul.cells > li > div.cell').each(function (i, li) {
                        var cell = false;
                        if (!$(li).hasClass('empty')) {
                            cell = {};
                            self.dataAttributeList.forEach(function (attr) {
                                if (attr === 'customLabel') {
                                    if ($(li).get(0).hasAttribute('data-custom-label')) {
                                        cell[attr] = $(li).attr('data-custom-label');
                                    }
                                    return;
                                }
                                var value = $(li).data(Espo.Utils.toDom(attr)) || null;
                                if (value) {
                                    cell[attr] = value;
                                }
                            });
                        }
                        row.push(cell);
                    });
                    o.rows.push(row);
                });

                layout.push(o);
            }.bind(this));

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


