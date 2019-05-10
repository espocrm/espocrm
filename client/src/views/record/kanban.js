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

define('views/record/kanban', ['views/record/list'], function (Dep) {

    return Dep.extend({

        template: 'record/kanban',

        type: 'kanban',

        name: 'kanban',

        scope: null,

        showCount: true,

        buttonList: [],

        headerDisabled: false,

        layoutName: 'kanban',

        portalLayoutDisabled: false,

        itemViewName: 'views/record/kanban-item',

        rowActionsView: 'views/record/row-actions/default-kanban',

        minColumnWidthPx: 125,

        events: {
            'click a.link': function (e) {
                e.stopPropagation();
                if (!this.scope || this.selectable) {
                    return;
                }
                e.preventDefault();
                var id = $(e.currentTarget).data('id');
                var model = this.collection.get(id);

                var scope = this.getModelScope(id);

                var options = {
                    id: id,
                    model: model
                };
                if (this.options.keepCurrentRootUrl) {
                    options.rootUrl = this.getRouter().getCurrentUrl();
                }

                this.getRouter().navigate('#' + scope + '/view/' + id, {trigger: false});
                this.getRouter().dispatch(scope, 'view', options);
            },
            'click [data-action="groupShowMore"]': function (e) {
                var $target = $(e.currentTarget);
                var group = $target.data('name');
                this.groupShowMore(group);
            },
            'click .action': function (e) {
                Espo.Utils.handleAction(this, e);
            }
        },

        showMore: true,

        quickDetailDisabled: false,

        quickEditDisabled: false,

        listLayout: null,

        _internalLayout: null,

        buttonsDisabled: false,

        data: function () {
            return {
                scope: this.scope,
                header: this.header,
                topBar: this.displayTotalCount || this.buttonList.length && !this.buttonsDisabled,
                showCount: this.showCount && this.collection.total > 0,
                buttonList: this.buttonList,
                displayTotalCount: this.displayTotalCount && this.collection.total > 0,
                totalCount: this.collection.total,
                statusList: this.statusList,
                groupDataList: this.groupDataList,
                minTableWidthPx: this.minColumnWidthPx * this.statusList.length,
                isEmptyList: this.collection.models.length === 0,
                totalCountFormatted: this.getNumberUtil().formatInt(this.collection.total),
            };
        },

        init: function () {
            this.listLayout = this.options.listLayout || this.listLayout;
            this.type = this.options.type || this.type;

            this.layoutName = this.options.layoutName || this.layoutName || this.type;

            this.rowActionsView = _.isUndefined(this.options.rowActionsView) ? this.rowActionsView : this.options.rowActionsView;

            if (this.massActionsDisabled && !this.selectable) {
                this.checkboxes = false;
            }

            this.rowActionsDisabled = this.options.rowActionsDisabled || this.rowActionsDisabled;

            if ('buttonsDisabled' in this.options) {
                this.buttonsDisabled = this.options.buttonsDisabled;
            }
        },

        getModelScope: function (id) {
            return this.scope;
        },

        setup: function () {
            if (typeof this.collection === 'undefined') {
                throw new Error('Collection has not been injected into Record.List view.');
            }

            this.layoutLoadCallbackList = [];

            this.entityType = this.collection.name || null;
            this.scope = this.options.scope || this.entityType;

            this.events = Espo.Utils.clone(this.events);

            this.buttonList = Espo.Utils.clone(this.buttonList);

            if ('showCount' in this.options) {
                this.showCount = this.options.showCount;
            }
            this.displayTotalCount = this.showCount && this.getConfig().get('displayListViewRecordCount');

            if ('displayTotalCount' in this.options) {
                this.displayTotalCount = this.options.displayTotalCount;
            }

            if (this.getUser().isPortal() && !this.portalLayoutDisabled) {
                if (this.getMetadata().get(['clientDefs', this.scope, 'additionalLayouts', this.layoutName + 'Portal'])) {
                    this.layoutName += 'Portal';
                }
            }

            this.statusField = this.getMetadata().get(['scopes', this.scope, 'statusField']);

            if (!this.statusField) {
                throw new Error("No status field for entity type '" + this.scope + "'.");
            }
            this.statusList = Espo.Utils.clone(this.getMetadata().get(['entityDefs', this.scope, 'fields', this.statusField, 'options']));

            var statusIgnoreList = this.getMetadata().get(['scopes', this.scope, 'kanbanStatusIgnoreList']) || [];

            this.statusList = this.statusList.filter(function (item) {
                if (~statusIgnoreList.indexOf(item)) return;
                return true;
            }, this);

            this.seedCollection = this.collection.clone();
            this.seedCollection.reset();
            this.seedCollection.url = this.scope;
            this.seedCollection.maxSize = this.collection.maxSize;
            this.seedCollection.name = this.collection.name;
            this.seedCollection.orderBy = this.collection.defaultOrderBy;
            this.seedCollection.order = this.collection.defaultOrder;

            this.listenTo(this.collection, 'sync', function (c, r, options) {
                if (this.hasView('modal') && this.getView('modal').isRendered()) return;

                this.buildRows(function () {
                    this.render();
                }.bind(this));
            }, this);

            this.collection.listenTo(this.collection, 'change:' + this.statusField, this.onChangeGroup.bind(this), this);

            this.buildRows();

            this.once('remove', function () {
                $(window).off('resize.kanban');
                $(window).off('scroll.kanban-' + this.cid);
                $(window).off('resize.kanban-' + this.cid);
            });

            if (
                this.getAcl().checkScope(this.entityType, 'edit')
                &&
                !~this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit').indexOf(this.statusField)
            ) {

                this.statusFieldIsEditable = true;
            } else {
                this.statusFieldIsEditable = false;
            }
        },

        afterRender: function () {
            var $window = $(window);

            this.$listKanban = this.$el.find('.list-kanban');
            this.$content = $('#content');

            $window.off('resize.kanban');
            $window.on('resize.kanban', function() {
                this.adjustMinHeight();
            }.bind(this));

            this.adjustMinHeight();

            if (this.statusFieldIsEditable) {
                this.initSortable();
            }

            this.initStickableHeader();
        },

        initStickableHeader: function () {
            var $container = this.$el.find('.kanban-head-container');
            var topBarHeight = this.getThemeManager().getParam('navbarHeight') || 30;

            var screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

            var $middle = this.$el.find('.kanban-columns-container');
            var $window = $(window);

            var $block = $('<div>').addClass('.kanban-head-paceholder').html('&nbsp;').hide().insertAfter($container);

            $window.off('scroll.kanban-' + this.cid);
            $window.on('scroll.kanban-' + this.cid, function (e) {
                cotrolSticking();
            }.bind(this));

            $window.off('resize.kanban-' + this.cid);
            $window.on('resize.kanban-' + this.cid, function (e) {
                cotrolSticking();
            }.bind(this));


            var cotrolSticking = function () {
                var width = $middle.width();

                if ($(window.document).width() < screenWidthXs) {
                    $container.removeClass('sticked');
                    $container.css('width', '');
                    $block.hide();
                    $container.show();
                    return;
                }

                var stickTop = this.$listKanban.position().top - topBarHeight;

                var edge = $middle.position().top + $middle.outerHeight(true);
                var scrollTop = $window.scrollTop();

                if (scrollTop < edge) {
                    if (scrollTop > stickTop) {
                        $container.css('width', width + 'px');

                        if (!$container.hasClass('sticked')) {
                            $container.addClass('sticked');
                            $block.show();
                        }
                    } else {
                        $container.css('width', '');
                        if ($container.hasClass('sticked')) {
                            $container.removeClass('sticked');
                            $block.hide();
                        }
                    }
                    $container.show();
                } else {
                    $container.css('width', width + 'px');
                    $container.hide();
                    $block.show();
                }
            }.bind(this);
        },

        initSortable: function () {
            var $item = this.$listKanban.find('.item');
            var $list = this.$listKanban.find('.group-column-list');

            $list.find('> .item').on('touchstart', function (e) {
                e.originalEvent.stopPropagation();
            }.bind(this));

            $list.sortable({
                connectWith: '.group-column-list',
                cancel: '.dropdown-menu *',
                start: function (e, ui) {
                    if (this.isItemBeingMoved) {

                    }
                    this.draggedGroupFrom = $(ui.item).closest('.group-column-list').data('name');
                }.bind(this),
                stop: function (e, ui) {
                    var $item = $(ui.item);
                    var group = $item.closest('.group-column-list').data('name');
                    var id = $item.data('id');

                    var draggedGroupFrom = this.draggedGroupFrom;
                    this.draggedGroupFrom = null;

                    if (group !== draggedGroupFrom) {
                        var model = this.collection.get(id);
                        if (!model) {
                            $list.sortable('cancel');
                            return;
                        }
                        var attributes = {};
                        attributes[this.statusField] = group;
                        this.handleAttributesOnGroupChange(model, attributes, group);

                        $list.sortable('disable');

                        model.save(attributes, {patch: true, isDrop: true}).then(function () {
                            Espo.Ui.success(this.translate('Saved'));
                            $list.sortable('destroy');
                            this.initSortable();
                        }.bind(this)).fail(function () {
                            $list.sortable('cancel');
                            $list.sortable('enable');
                        }.bind(this));
                    } else {
                        $list.sortable('cancel');
                        $list.sortable('enable');
                    }
                }.bind(this)
            });
        },

        handleAttributesOnGroupChange: function (model, attributes, group) {},

        adjustMinHeight: function () {
            if (this.collection.models.length === 0) return;

            var top = this.$listKanban.find('table > tbody').position().top;
            var bottom = this.$content.position().top + this.$content.outerHeight(true);

            var height = bottom - top;

            height = height - 100;

            if (height < 100) {
                height = 100;
            }

            this.$listKanban.find('td.group-column > div').css({
                minHeight: height + 'px'
            });
        },

        getListLayout: function (callback) {
            if (this.listLayout) {
                callback.call(this, this.listLayout);
                return;
            }

            this._loadListLayout(function (listLayout) {
                this.listLayout = listLayout;
                callback.call(this, listLayout);
            }.bind(this));
        },

        getSelectAttributeList: function (callback) {
            Dep.prototype.getSelectAttributeList.call(this, function (attrubuteList) {
                if (attrubuteList) {
                    if (!~attrubuteList.indexOf(this.statusField)) {
                        attrubuteList.push(this.statusField);
                    }
                }
                callback(attrubuteList);
            }.bind(this));
        },

        buildRows: function (callback) {
            var groupList = (this.collection.dataAdditional || {}).groupList || [];

            this.collection.reset();

            this.collection.subCollectionList = [];

            this.wait(true);

            this.groupDataList = [];

            var count = 0;
            var loadedCount = 0;

            this.getListLayout(function (listLayout) {
                this.listLayout = listLayout;

                groupList.forEach(function (item, i) {
                    var collection = this.seedCollection.clone();
                    collection.total = item.total;
                    collection.url = this.scope;
                    collection.where = this.collection.where;
                    collection.name = this.seedCollection.name;
                    collection.maxSize = this.seedCollection.maxSize;
                    collection.orderBy = this.seedCollection.orderBy;
                    collection.order = this.seedCollection.order;
                    collection.whereAdditional = [
                        {
                            field: this.statusField,
                            type: 'equals',
                            value: item.name
                        }
                    ];
                    collection.groupName = item.name;
                    collection.set(item.list);

                    this.collection.subCollectionList.push(collection);

                    this.collection.add(collection.models);

                    var itemDataList = [];

                    collection.models.forEach(function (model) {
                        count ++;
                        itemDataList.push({
                            key: model.id,
                            id: model.id
                        });
                    }, this);

                    var nextStyle = null;
                    if (i < groupList.length - 1) {
                        nextStyle = this.getMetadata().get(['entityDefs', this.scope, 'fields', this.statusField, 'style', groupList[i + 1].name]);
                    }

                    var o = {
                        name: item.name,
                        label: this.getLanguage().translateOption(item.name, this.statusField, this.scope),
                        dataList: itemDataList,
                        collection: collection,
                        isLast: i === groupList.length - 1,
                        hasShowMore: collection.total > collection.length || collection.total == -1,
                        style: this.getMetadata().get(['entityDefs', this.scope, 'fields', this.statusField, 'style', item.name]),
                        nextStyle: nextStyle
                    };

                    this.groupDataList.push(o);
                }, this);

                if (count === 0) {
                    this.wait(false);
                    if (callback) {
                        callback();
                    }
                } else {
                    this.groupDataList.forEach(function (groupItem) {
                        groupItem.dataList.forEach(function (item, j) {
                            var model = groupItem.collection.get(item.id);
                            this.buildRow(j, model, function (view) {
                                loadedCount++;
                                if (loadedCount === count) {
                                    this.wait(false);
                                    if (callback) {
                                        callback();
                                    }
                                }
                            });
                        }, this);
                    }, this);
                }
            });
        },

        buildRow: function (i, model, callback) {
            var key = model.id;

            this.createView(key, this.itemViewName, {
                model: model,
                el: this.getSelector() + ' .item[data-id="'+model.id+'"]',
                itemLayout: this.listLayout,
                rowActionsDisabled: this.rowActionsDisabled,
                rowActionsView: this.rowActionsView,
                setViewBeforeCallback: this.options.skipBuildRows && !this.isRendered(),
                statusFieldIsEditable: this.statusFieldIsEditable
            }, callback);
        },

        removeRecordFromList: function (id) {
            this.collection.remove(id);
            if (this.collection.total > 0) {
                this.collection.total--;
            }
            this.totalCount = this.collection.total;

            this.$el.find('.total-count-span').text(this.totalCount.toString());

            this.clearView(id);

            this.$el.find('.item[data-id="'+id+'"]').remove();

            this.collection.subCollectionList.forEach(function (collection) {
                if (collection.get(id)) {
                    collection.remove(id);
                }
            }, this);

            for (var i in this.groupDataList) {
                var groupItem = this.groupDataList[i];
                for (var j in groupItem.dataList) {
                    var item = groupItem.dataList[j];
                    if (item.id === id) {
                        groupItem.dataList.splice(j, 1);
                        if (groupItem.collection.total > 0) {
                            groupItem.collection.total--;
                        }
                        groupItem.hasShowMore = groupItem.collection.total > groupItem.collection.length || groupItem.collection.total == -1;
                        break;
                    }
                }
            }
        },

        onChangeGroup: function (model, value, o) {
            var id = model.id;
            var group = model.get(this.statusField);

            this.collection.subCollectionList.forEach(function (collection) {
                if (collection.get(id)) {
                    collection.remove(id);
                    if (collection.total > 0) {
                        collection.total--;
                    }
                }
            }, this);

            var dataItem;

            for (var i in this.groupDataList) {
                var groupItem = this.groupDataList[i];
                for (var j in groupItem.dataList) {
                    var item = groupItem.dataList[j];
                    if (item.id === id) {
                        dataItem = item;
                        groupItem.dataList.splice(j, 1);
                        break;
                    }
                }
            }

            if (!group) return;
            if (o.isDrop) return;

            for (var i in this.groupDataList) {
                var groupItem = this.groupDataList[i];
                if (groupItem.name !== group) continue;
                groupItem.collection.unshift(model);
                groupItem.collection.total++;
                if (dataItem) {
                    groupItem.dataList.unshift(dataItem);
                    groupItem.hasShowMore = groupItem.collection.total > groupItem.collection.length || groupItem.collection.total == -1;
                }
            }

            var $item = this.$el.find('.item[data-id="'+id+'"]');
            var $column = this.$el.find('.group-column[data-name="'+group+'"] .group-column-list');

            if ($column.length) {
                $column.prepend($item);
            } else {
                $item.remove();
            }
        },

        groupShowMore: function (group) {
            for (var i in this.groupDataList) {
                var groupItem = this.groupDataList[i];
                if (groupItem.name === group) {
                    break;
                } else {
                    groupItem = null;
                }
            }

            if (!groupItem) return;

            var collection = groupItem.collection;
            var $list = this.$el.find('.group-column-list[data-name="'+group+'"]');
            var $showMore = this.$el.find('.group-column[data-name="'+group+'"] .show-more');

            collection.data.select = this.collection.data.select;

            this.showMoreRecords(collection, $list, $showMore, function () {
                this.noRebuild = false;
                collection.models.forEach(function (model) {
                    if (this.collection.get(model.id)) return;
                    this.collection.add(model);
                    groupItem.dataList.push({
                        key: model.id,
                        id: model.id
                    });
                }, this);
            });
        },

        getRowContainerHtml: function (id) {
            return '<div class="item" data-id="'+id+'">';
        },

        actionMoveOver: function (data) {
            var model = this.collection.get(data.id);

            this.createView('moveOverDialog', 'views/modals/kanban-move-over', {
                model: model,
                statusField: this.statusField
            }, function (view) {
                view.render();
            });
        }

    });
});
