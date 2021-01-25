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

define('views/record/list-tree-item', 'view', function (Dep) {

    return Dep.extend({

        template: 'record/list-tree-item',

        isEnd: false,

        level: 0,

        listViewName: 'views/record/list-tree',

        data: function () {
            return {
                name: this.model.get('name'),
                isUnfolded: this.isUnfolded,
                showFold: this.isUnfolded && !this.isEnd,
                showUnfold: !this.isUnfolded && !this.isEnd,
                isEnd: this.isEnd,
                isSelected: this.isSelected,
                readOnly: this.readOnly,
            };
        },

        events: {
            'click [data-action="unfold"]': function (e) {
                this.unfold();
                e.stopPropagation();
            },
            'click [data-action="fold"]': function (e) {
                this.fold();
                e.stopPropagation();
            },
            'click [data-action="remove"]': function (e) {
                this.actionRemove();
                e.stopPropagation();
            }
        },

        setIsSelected: function () {
            this.isSelected = true;
            this.selectedData.id = this.model.id;

            var path = this.selectedData.path;
            var names = this.selectedData.names;
            path.length = 0;

            var view = this;

            while (1) {
                path.unshift(view.model.id);
                names[view.model.id] = view.model.get('name');

                if (view.getParentView().level) {
                    view = view.getParentView().getParentView();
                } else {
                    break;
                }
            }
        },

        setup: function () {
            if ('level' in this.options) {
                this.level = this.options.level;
            }

            if ('isSelected' in this.options) {
                this.isSelected = this.options.isSelected;
            }

            if ('selectedData' in this.options) {
                this.selectedData = this.options.selectedData;
            }

            this.readOnly = this.options.readOnly;

            if ('createDisabled' in this.options) {
                this.createDisabled = this.options.createDisabled;
            }

            if (this.readOnly) {
                this.createDisabled = true;
            }

            this.rootView = this.options.rootView;

            this.scope = this.model.name;

            this.isUnfolded = false;

            var childCollection = this.model.get('childCollection');

            if ((childCollection && childCollection.length == 0) || this.model.isEnd) {
                if (this.createDisabled) {
                    this.isEnd = true;
                }
            } else {
                if (childCollection) {
                    childCollection.models.forEach(function (model) {
                        if (~this.selectedData.path.indexOf(model.id)) {
                            this.isUnfolded = true;
                        }
                    }, this);
                    if (this.isUnfolded) {
                        this.createChildren();
                    }
                }
            }

            this.on('select', function (o) {
                this.getParentView().trigger('select', o);
            }, this);
        },

        createChildren: function () {
            var childCollection = this.model.get('childCollection');

            var callback = null;

            if (this.isRendered()) {
                callback = function (view) {
                    this.listenToOnce(view, 'after:render', function () {
                        this.trigger('children-created');
                    }, this);

                    view.render();
                }.bind(this);
            }

            this.createView('children', this.listViewName, {
                collection: childCollection,
                el: this.options.el + ' > .children',
                createDisabled: this.options.createDisabled,
                readOnly: this.options.readOnly,
                level: this.level + 1,
                selectedData: this.selectedData,
                model: this.model,
                selectable: this.options.selectable,
                rootView: this.rootView,
            }, callback);
        },

        checkLastChildren: function () {
            this.ajaxGetRequest(this.collection.name + '/action/lastChildrenIdList', {
                parentId: this.model.id
            }).then(function (idList) {
                var childrenView = this.getView('children');

                idList.forEach(function (id) {
                    var model = this.model.get('childCollection').get(id);

                    if (model) {
                        model.isEnd = true;
                    }

                    var itemView = childrenView.getView(id);

                    if (!itemView) {
                        return;
                    }

                    itemView.isEnd = true;

                    itemView.afterIsEnd();
                }, this);

                this.model.lastAreChecked = true;
            }.bind(this));
        },

        unfold: function () {
            if (this.createDisabled) {
                this.once('children-created', function () {
                    var childrenView = this.getView('children');

                    if (!this.model.lastAreChecked) {
                        this.checkLastChildren();
                    }
                }, this);
            }

            var childCollection = this.model.get('childCollection');

            if (childCollection !== null) {
                this.createChildren();

                this.isUnfolded = true;

                this.afterUnfold();

                this.trigger('after:unfold');
            } else {
                this.getCollectionFactory().create(this.scope, function (collection) {
                    collection.url = this.collection.url;
                    collection.parentId = this.model.id;
                    collection.maxDepth = 1;

                    Espo.Ui.notify(this.translate('loading', 'messages'));

                    this.listenToOnce(collection, 'sync', function () {

                    this.notify(false);
                        this.model.set('childCollection', collection);

                        this.createChildren();

                        this.isUnfolded = true;

                        if (collection.length || !this.createDisabled) {
                            this.afterUnfold();

                            this.trigger('after:unfold');
                        } else {
                            this.isEnd = true;

                            this.afterIsEnd();
                        }
                    }, this);

                    collection.fetch();
                }, this);
            }
        },

        fold: function () {
            this.clearView('children');

            this.isUnfolded = false;

            this.afterFold();
        },

        afterRender: function () {
            if (this.isUnfolded) {
                this.afterUnfold();
            } else {
                this.afterFold();
            }

            if (this.isEnd) {
                this.afterIsEnd();
            }

            if (!this.readOnly) {
                var $remove = this.$el.find('> .cell [data-action="remove"]');

                this.$el.find('> .cell').on('mouseenter', function ($el) {
                    $remove.removeClass('hidden');
                });

                this.$el.find('> .cell').on('mouseleave', function ($el) {
                    $remove.addClass('hidden');
                });
            }
        },

        afterFold: function () {
            this.$el.find('a[data-action="fold"][data-id="'+this.model.id+'"]').addClass('hidden');
            this.$el.find('a[data-action="unfold"][data-id="'+this.model.id+'"]').removeClass('hidden');
            this.$el.find(' > .children').addClass('hidden');
        },

        afterUnfold: function () {
            this.$el.find('a[data-action="unfold"][data-id="'+this.model.id+'"]').addClass('hidden');
            this.$el.find('a[data-action="fold"][data-id="'+this.model.id+'"]').removeClass('hidden');
            this.$el.find(' > .children').removeClass('hidden');
        },

        afterIsEnd: function () {
            this.$el.find('a[data-action="unfold"][data-id="'+this.model.id+'"]').addClass('hidden');
            this.$el.find('a[data-action="fold"][data-id="'+this.model.id+'"]').addClass('hidden');
            this.$el.find('span[data-name="white-space"][data-id="'+this.model.id+'"]').removeClass('hidden');
            this.$el.find(' > .children').addClass('hidden');
        },

        getCurrentPath: function () {
            var pointer = this;

            var path = [];

            while (true) {
                path.unshift(pointer.model.id);

                if (pointer.getParentView() === this.rootView) {
                    break;
                }

                pointer = pointer.getParentView().getParentView();
            }

            return path;
        },

        actionRemove: function () {
            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages', this.scope),
                confirmText: this.translate('Remove'),
            }, function () {
                this.model
                    .destroy(
                        {
                            wait: true,
                        }
                    )
                    .then(
                        function () {
                            this.remove();
                        }
                        .bind(this)
                    );

            }.bind(this));
        },

    });
});
