/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

        minColumnWidthPx: 220,

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
                    model: model,
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
            },
            'mouseenter th.group-header': function (e) {
                let group = $(e.currentTarget).attr('data-name');

                this.showPlus(group);
            },
            'mouseleave th.group-header': function (e) {
                let group = $(e.currentTarget).attr('data-name');

                this.hidePlus(group);
            },
            'click [data-action="createInGroup"]': function (e) {
                let group = $(e.currentTarget).attr('data-group');

                this.actionCreateInGroup(group);
            },
            'mousedown .kanban-columns td': function (e) {
                if ($(e.originalEvent.target).closest('.item').length) {
                    return;
                }

                this.initBackDrag(e.originalEvent);
            },
        },

        showMore: true,

        quickDetailDisabled: false,

        quickEditDisabled: false,

        listLayout: null,

        _internalLayout: null,

        buttonsDisabled: false,

        backDragStarted: true,

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
                isCreatable: this.isCreatable,
            };
        },

        init: function () {
            this.listLayout = this.options.listLayout || this.listLayout;
            this.type = this.options.type || this.type;

            this.layoutName = this.options.layoutName || this.layoutName || this.type;

            this.rowActionsView = _.isUndefined(this.options.rowActionsView) ?
                this.rowActionsView :
                this.options.rowActionsView;

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
                if (
                    this.getMetadata()
                        .get(['clientDefs', this.scope, 'additionalLayouts', this.layoutName + 'Portal'])
                ) {
                    this.layoutName += 'Portal';
                }
            }

            this.orderDisabled = this.getMetadata().get(['scopes', this.scope, 'kanbanOrderDisabled']);

            if (this.getUser().isPortal()) {
                this.orderDisabled = true;
            }

            this.statusField = this.getMetadata().get(['scopes', this.scope, 'statusField']);

            if (!this.statusField) {
                throw new Error("No status field for entity type '" + this.scope + "'.");
            }

            this.statusList = Espo.Utils.clone(this.getMetadata().get(
                ['entityDefs', this.scope, 'fields', this.statusField, 'options'])
            );

            var statusIgnoreList = this.getMetadata().get(['scopes', this.scope, 'kanbanStatusIgnoreList']) || [];

            this.statusList = this.statusList.filter((item) => {
                if (~statusIgnoreList.indexOf(item)) {
                    return;
                }

                return true;
            });

            this.seedCollection = this.collection.clone();
            this.seedCollection.reset();
            this.seedCollection.url = this.scope;
            this.seedCollection.maxSize = this.collection.maxSize;
            this.seedCollection.name = this.collection.name;
            this.seedCollection.orderBy = this.collection.defaultOrderBy;
            this.seedCollection.order = this.collection.defaultOrder;

            this.listenTo(this.collection, 'sync', () => {
                if (this.hasView('modal') && this.getView('modal').isRendered()) {
                    return;
                }

                this.buildRows(() => {
                    this.render();
                });
            });

            this.collection.listenTo(
                this.collection,
                'change:' + this.statusField,
                this.onChangeGroup.bind(this),
                this
            );

            this.buildRows();

            this.once('remove', () => {
                $(window).off('resize.kanban');
                $(window).off('scroll.kanban-' + this.cid);
                $(window).off('resize.kanban-' + this.cid);
            });

            if (
                this.getAcl().checkScope(this.entityType, 'edit') &&
                !~this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit').indexOf(this.statusField) &&
                !this.getMetadata().get(['clientDefs', this.scope, 'editDisabled'])
            ) {
                this.statusFieldIsEditable = true;
            } else {
                this.statusFieldIsEditable = false;
            }

            this.isCreatable = this.statusFieldIsEditable && this.getAcl().check(this.entityType, 'create');

            this.getHelper().processSetupHandlers(this, 'record/kanban');
        },

        afterRender: function () {
            var $window = $(window);

            this.$listKanban = this.$el.find('.list-kanban');
            this.$content = $('#content');

            this.$groupColumnList = this.$listKanban.find('.group-column-list');

            this.$container = this.$el.find('.list-kanban-container');

            $window.off('resize.kanban');
            $window.on('resize.kanban', () => {
                this.adjustMinHeight();
            });

            this.adjustMinHeight();

            if (this.statusFieldIsEditable) {
                this.initSortable();
            }

            this.initStickableHeader();

            this.$showMore = this.$el.find('.group-column .show-more');

            this.plusElementMap = {};

            this.statusList.forEach(status => {
                let value = status.replace(/"/g, '\\"');

                this.plusElementMap[status] = this.$el
                    .find('.kanban-head .create-button[data-group="' + value + '"]');
            });
        },

        initStickableHeader: function () {
            var $container = this.$el.find('.kanban-head-container');
            var topBarHeight = this.getThemeManager().getParam('navbarHeight') || 30;

            var screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

            var $middle = this.$el.find('.kanban-columns-container');
            var $window = $(window);

            var $block = $('<div>')
                .addClass('.kanban-head-paceholder')
                .html('&nbsp;')
                .hide()
                .insertAfter($container);

            $window.off('scroll.kanban-' + this.cid);

            $window.on('scroll.kanban-' + this.cid, () => {
                controlSticking();
            });

            $window.off('resize.kanban-' + this.cid);
            $window.on('resize.kanban-' + this.cid, () => {
                controlSticking();
            });

            var controlSticking = () => {
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
            };
        },

        initSortable: function () {
            var $list = this.$groupColumnList;

            $list.find('> .item').on('touchstart', (e) => {
                e.originalEvent.stopPropagation();
            });

            var orderDisabled = this.orderDisabled;

            let $grouoColumnList = this.$el.find('.group-column-list');

            $list.sortable({
                connectWith: '.group-column-list',
                cancel: '.dropdown-menu *',
                containment: this.getSelector(),
                scroll: false,
                over: function (e, ui) {
                    $(this).addClass('drop-hover');
                },
                out: function (e, ui) {
                    $(this).removeClass('drop-hover');
                },
                sort: (e, ui) => {
                    if (!this.blockScrollControl) {
                        this.controlHorizontalScroll(e.originalEvent);
                    }
                },
                start: (e, ui) => {
                    $grouoColumnList.addClass('drop-active');

                    $list.sortable('refreshPositions');

                    this.draggedGroupFrom = $(ui.item).closest('.group-column-list').data('name');
                    this.$showMore.addClass('hidden');

                    this.sortIsStarted = true;
                    this.sortWasCentered = false;

                    this.$draggable = ui.item;
                },
                stop: (e, ui) => {
                    this.blockScrollControl = false;
                    this.sortIsStarted = false;
                    this.$draggable = null;

                    var $item = $(ui.item);

                    this.$el.find('.group-column-list').removeClass('drop-active');

                    var group = $item.closest('.group-column-list').data('name');
                    var id = $item.data('id');

                    var draggedGroupFrom = this.draggedGroupFrom;

                    this.draggedGroupFrom = null;

                    this.$showMore.removeClass('hidden');

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

                        model
                            .save(attributes, {
                                patch: true,
                                isDrop: true,
                            })
                            .then(() => {
                                Espo.Ui.success(this.translate('Saved'));

                                $list.sortable('destroy');

                                this.initSortable();

                                this.moveModelBetweenGroupCollections(model, draggedGroupFrom, group);

                                if (!orderDisabled) {
                                    this.reOrderGroup(group);
                                    this.storeGroupOrder(group);
                                }

                                this.rebuildGroupDataList();
                            })
                            .catch(() => {
                                $list.sortable('cancel');
                                $list.sortable('enable');
                            });

                        return;
                    }

                    if (orderDisabled) {
                        $list.sortable('cancel');
                        $list.sortable('enable');

                        return;
                    }

                    this.reOrderGroup(group);
                    this.storeGroupOrder(group);
                    this.rebuildGroupDataList();
                },
            });
        },

        storeGroupOrder: function (group) {
            Espo.Ajax.postRequest('Kanban/action/storeOrder', {
                entityType: this.entityType,
                group: group,
                ids: this.getGroupOrderFromDom(group),
            });
        },

        getGroupOrderFromDom: function (group) {
            var ids = [];

            var $group = this.$el.find('.group-column-list[data-name="'+group+'"]');

            $group.children().each((i, el) => {
                ids.push($(el).data('id'));
            });

            return ids;
        },

        reOrderGroup: function (group) {
            var groupCollection = this.getGroupCollection(group);

            var ids = this.getGroupOrderFromDom(group);

            var modelMap = {};

            groupCollection.models.forEach((m) => {
                modelMap[m.id] = m;
            });

            while (groupCollection.models.length) {
                groupCollection.pop({silent: true});
            }

            ids.forEach(function (id) {
                var model = modelMap[id];

                if (!model) {
                    return;
                }

                groupCollection.add(model, {silent: true});
            });
        },

        rebuildGroupDataList: function () {
            this.groupDataList.forEach((item) => {
                item.dataList = [];

                for (var model of item.collection.models) {
                    item.dataList.push({
                        key: model.id,
                        id: model.id,
                    });
                }
            });
        },

        moveModelBetweenGroupCollections: function (model, groupFrom, groupTo) {
            var collection = this.getGroupCollection(groupFrom);

            if (!collection) {
                return;
            }

            collection.remove(model.id, {silent: true});

            var collection = this.getGroupCollection(groupTo);

            if (!collection) {
                return;
            }

            collection.add(model, {silent: true});
        },

        handleAttributesOnGroupChange: function (model, attributes, group) {},

        adjustMinHeight: function () {
            if (this.collection.models.length === 0) {
                return;
            }

            var containerHeight = this.getHelper()
                .calculateContentContainerHeight(this.$el.find('.kanban-columns-container'));

            var height = containerHeight;

            let containerEl = this.$container.get(0);

            if (containerEl.scrollWidth > containerEl.clientWidth) {
                height -= 18;
            }

            if (height < 100) {
                height = 100;
            }

            this.$listKanban.find('td.group-column').css({
                minHeight: height + 'px',
            });
        },

        getListLayout: function (callback) {
            if (this.listLayout) {
                callback.call(this, this.listLayout);

                return;
            }

            this._loadListLayout((listLayout) => {
                this.listLayout = listLayout;
                callback.call(this, listLayout);
            });
        },

        getSelectAttributeList: function (callback) {
            Dep.prototype.getSelectAttributeList.call(this, (attrubuteList) => {
                if (attrubuteList) {
                    if (!~attrubuteList.indexOf(this.statusField)) {
                        attrubuteList.push(this.statusField);
                    }
                }

                callback(attrubuteList);
            });
        },

        buildRows: function (callback) {
            var groupList = (this.collection.dataAdditional || {}).groupList || [];

            this.collection.reset();

            this.collection.subCollectionList = [];

            this.wait(true);

            this.groupDataList = [];

            var count = 0;
            var loadedCount = 0;

            this.getListLayout((listLayout) => {
                this.listLayout = listLayout;

                groupList.forEach((item, i) => {
                    var collection = this.seedCollection.clone();

                    collection.total = item.total;

                    collection.url = this.collection.url;

                    collection.where = this.collection.where;
                    collection.name = this.seedCollection.name;
                    collection.maxSize = this.seedCollection.maxSize;
                    collection.orderBy = this.seedCollection.orderBy;
                    collection.order = this.seedCollection.order;

                    collection.whereAdditional = [
                        {
                            field: this.statusField,
                            type: 'equals',
                            value: item.name,
                        }
                    ];

                    collection.groupName = item.name;
                    collection.set(item.list);

                    this.collection.subCollectionList.push(collection);

                    this.collection.add(collection.models);

                    var itemDataList = [];

                    collection.models.forEach((model) => {
                        count ++;

                        itemDataList.push({
                            key: model.id,
                            id: model.id,
                        });
                    });

                    var nextStyle = null;

                    if (i < groupList.length - 1) {
                        nextStyle = this.getMetadata().get(
                            ['entityDefs', this.scope, 'fields', this.statusField, 'style', groupList[i + 1].name]
                        );
                    }

                    var o = {
                        name: item.name,
                        label: this.getLanguage().translateOption(item.name, this.statusField, this.scope),
                        dataList: itemDataList,
                        collection: collection,
                        isLast: i === groupList.length - 1,
                        hasShowMore: collection.total > collection.length || collection.total == -1,
                        style: this.getMetadata().get(
                            ['entityDefs', this.scope, 'fields', this.statusField, 'style', item.name]
                        ),
                        nextStyle: nextStyle,
                    };

                    this.groupDataList.push(o);
                });

                if (count === 0) {
                    this.wait(false);

                    if (callback) {
                        callback();
                    }
                } else {
                    this.groupDataList.forEach((groupItem) => {
                        groupItem.dataList.forEach((item, j) => {
                            var model = groupItem.collection.get(item.id);

                            this.buildRow(j, model, (view) => {
                                loadedCount++;

                                if (loadedCount === count) {
                                    this.wait(false);

                                    if (callback) {
                                        callback();
                                    }
                                }
                            });
                        });
                    });
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
                statusFieldIsEditable: this.statusFieldIsEditable,
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

            this.collection.subCollectionList.forEach((collection) => {
                if (collection.get(id)) {
                    collection.remove(id);
                }
            });

            for (var i in this.groupDataList) {
                var groupItem = this.groupDataList[i];

                for (var j in groupItem.dataList) {
                    var item = groupItem.dataList[j];

                    if (item.id === id) {
                        groupItem.dataList.splice(j, 1);

                        if (groupItem.collection.total > 0) {
                            groupItem.collection.total--;
                        }

                        groupItem.hasShowMore = groupItem.collection.total > groupItem.collection.length ||
                            groupItem.collection.total == -1;

                        break;
                    }
                }
            }
        },

        onChangeGroup: function (model, value, o) {
            var id = model.id;
            var group = model.get(this.statusField);

            this.collection.subCollectionList.forEach((collection) => {
                if (collection.get(id)) {
                    collection.remove(id);

                    if (collection.total > 0) {
                        collection.total--;
                    }
                }
            });

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

            if (!group) {
                return;
            }

            if (o.isDrop) {
                return;
            }

            for (var i in this.groupDataList) {
                var groupItem = this.groupDataList[i];

                if (groupItem.name !== group) {
                    continue;
                }

                groupItem.collection.unshift(model);
                groupItem.collection.total++;

                if (dataItem) {
                    groupItem.dataList.unshift(dataItem);

                    groupItem.hasShowMore = groupItem.collection.total > groupItem.collection.length ||
                        groupItem.collection.total === -1;
                }
            }

            var $item = this.$el.find('.item[data-id="'+id+'"]');
            var $column = this.$el.find('.group-column[data-name="'+group+'"] .group-column-list');

            if ($column.length) {
                $column.prepend($item);
            } else {
                $item.remove();
            }

            if (!this.orderDisabled) {
                this.storeGroupOrder(group);
            }
        },

        groupShowMore: function (group) {
            for (var i in this.groupDataList) {
                var groupItem = this.groupDataList[i];

                if (groupItem.name === group) {
                    break;
                }

                groupItem = null;
            }

            if (!groupItem) {
                return;
            }

            var collection = groupItem.collection;

            var $list = this.$el.find('.group-column-list[data-name="'+group+'"]');
            var $showMore = this.$el.find('.group-column[data-name="'+group+'"] .show-more');

            collection.data.select = this.collection.data.select;

            this.showMoreRecords(collection, $list, $showMore, () => {
                this.noRebuild = false;

                collection.models.forEach((model) => {
                    if (this.collection.get(model.id)) {
                        return;
                    }

                    this.collection.add(model);

                    groupItem.dataList.push({
                        key: model.id,
                        id: model.id,
                    });
                });
            });
        },

        getDomRowItem: function (id) {
            return this.$el.find('.item[data-id="'+id+'"]');
        },

        getRowContainerHtml: function (id) {
            return '<div class="item" data-id="'+id+'">';
        },

        actionMoveOver: function (data) {
            var model = this.collection.get(data.id);

            this.createView('moveOverDialog', 'views/modals/kanban-move-over', {
                model: model,
                statusField: this.statusField,
            }, (view) => {
                view.render();
            });
        },

        getGroupCollection: function (group) {
            var collection = null;

            this.collection.subCollectionList.forEach((itemCollection) => {
                if (itemCollection.groupName === group) {
                    collection = itemCollection;
                }
            });

            return collection;
        },

        showPlus: function (group) {
            let $el = this.plusElementMap[group];

            if (!$el) {
                return;
            }

            $el.removeClass('hidden');
        },

        hidePlus: function (group) {
            let $el = this.plusElementMap[group];

            if (!$el) {
                return;
            }

            $el.addClass('hidden');
        },

        actionCreateInGroup: function (group) {
            let attributes = {};

            attributes[this.statusField] = group;

            var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.edit') ||
                'views/modals/edit';

            let options = {
                attributes: attributes,
                scope: this.scope,
            };

            this.createView('quickCreate', viewName, options, (view) => {
                view.render();

                this.listenToOnce(view, 'after:save', () => {
                    this.collection.fetch();
                });
            });
        },

        initBackDrag: function (e) {
            this.backDragStarted = true;

            let containerEl = this.$container.get(0);

            containerEl.style.cursor = 'grabbing';
            containerEl.style.userSelect = 'none';

            let $document = $(document);

            let startLeft = containerEl.scrollLeft;
            let startX = e.clientX;

            $document.on('mousemove.' + this.cid, (e) => {
                let dx = e.originalEvent.clientX - startX;

                containerEl.scrollLeft = startLeft - dx;
            });

            $document.one('mouseup.' + this.cid, () => {
                this.stopBackDrag();
            });
        },

        stopBackDrag: function () {
            this.$container.get(0).style.cursor = 'default';
            this.$container.get(0).style.userSelect = 'none';

            $(document).off('mousemove.' + this.cid);
        },

        controlHorizontalScroll: function (e) {
            if (!this.sortIsStarted) {
                return;
            }

            if (!this.$draggable) {
                return;
            }

            let draggableRect = this.$draggable.get(0).getBoundingClientRect();

            let itemLeft = draggableRect.left;
            let itemRight = draggableRect.right;

            let containerEl = this.$container.get(0);

            let rect = containerEl.getBoundingClientRect();

            let marginSens = 70;
            let step = 2;
            let interval = 5;
            let marginSensStepRatio = 4;
            let stepRatio = 3;

            let isRight = rect.right - marginSens < itemRight &&
                containerEl.scrollLeft + containerEl.offsetWidth < containerEl.scrollWidth;

            let isLeft = rect.left + marginSens > itemLeft &&
                containerEl.scrollLeft > 0;

            this.$groupColumnList.sortable('refreshPositions');

            if (isRight && this.sortWasCentered) {
                let margin = rect.right - itemRight;

                if (margin < marginSens / marginSensStepRatio) {
                    step *= stepRatio;
                }

                let stepActual = Math.min(step, containerEl.offsetWidth - containerEl.scrollLeft);

                containerEl.scrollLeft = containerEl.scrollLeft + stepActual;

                if (containerEl.scrollLeft + containerEl.offsetWidth === containerEl.scrollWidth) {
                    this.blockScrollControl = false;

                    return;
                }

                this.blockScrollControl = true;

                setTimeout(() => this.controlHorizontalScroll(e), interval);

                return;
            }

            if (isLeft && this.sortWasCentered) {
                let margin = - (rect.left - itemLeft);

                if (margin < marginSens / marginSensStepRatio) {
                    step *= stepRatio;
                }

                let stepActual = Math.min(step, containerEl.scrollLeft);

                containerEl.scrollLeft = containerEl.scrollLeft - stepActual;

                if (containerEl.scrollLeft === 0) {
                    this.blockScrollControl = false;

                    return;
                }

                this.blockScrollControl = true;

                setTimeout(() => this.controlHorizontalScroll(e), interval);

                return;
            }

            if (this.blockScrollControl && !isLeft && !isRight) {
                this.blockScrollControl = false;
            }

            if (!isLeft && !isRight) {
                this.sortWasCentered = true;
            }
        },

    });
});
