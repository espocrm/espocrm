/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
Espo.define('Views.Record.Detail', 'Views.Record.Base', function (Dep) {

    return Dep.extend({

        template: 'record.detail',

        type: 'detail',

        name: 'detail',

        layoutName: 'detail',

        fieldsMode: 'detail',

        gridLayout: null,

        /**
         * @property {string} or {bool} ['both', 'top', 'bottom', false, true] Where to display buttons.
         */
        buttonsPosition: 'top',

        columnCount: 2,

        scope: null,

        isNew: false,

        buttonList: [
            {
                name: 'edit',
                label: 'Edit',
                style: 'primary',
            },
            {
                name: 'delete',
                label: 'Delete',
                style: 'danger',
            },
        ],

        buttonEditList: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
                edit: true,
            },
            {
                name: 'cancelEdit',
                label: 'Cancel',
                edit: true,
            }
        ],

        id: null,

        returnUrl: null,

        sideView: 'Record.DetailSide',

        bottomView: 'Record.DetailBottom',

        editModeEnabled: true,

        readOnly: false,

        isWide: false,

        dependencyDefs: {},

        events: {
            'click .button-container button': function (e) {
                var $target = $(e.currentTarget);
                var action = $target.data('action');
                var data = $target.data();
                if (action) {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);
                    if (typeof this[method] == 'function') {
                        this[method].call(this, data);
                    }
                }
            }
        },

        setConfirmLayout: function (value) {
            this.getRouter().confirmLayout = value;
        },

        actionEdit: function () {
            if (this.editModeEnabled) {
                this.setEditMode();
                $(window).scrollTop(0);
            } else {
                this.getRouter().navigate('#' + this.scope + '/edit/' + this.model.id, {trigger: true});
            }
        },

        actionDelete: function () {
            this.delete();
        },

        actionSave: function () {
            if (this.save()) {
                this.setDetailMode();
                $(window).scrollTop(0)
            }
        },

        actionCancelEdit: function () {
            this.cancelEdit();
            $(window).scrollTop(0);
        },

        afterRender: function () {
            var $container = this.$el.find('.detail-button-container');
            var stickTop = 62;
            var blockHeihgt = 21;
            var $block = $('<div>').css('height', blockHeihgt + 'px').html('&nbsp;').hide().insertAfter($container);
            var $record = this.getView('record').$el;
            var $window = $(window);

            $window.off('scroll.detail-' + this.numId);
            $window.on('scroll.detail-' + this.numId, function (e) {
                if ($(window.document).width() < 758) {
                    $container.removeClass('stick-sub');
                    $block.hide();
                    $container.show();
                    return;
                }

                var edge = $record.position().top + $record.outerHeight(true);
                var scrollTop = $window.scrollTop();

                if (scrollTop < edge) {
                    if (scrollTop > stickTop) {
                        if (!$container.hasClass('stick-sub')) {
                            $container.addClass('stick-sub');
                            $block.show();

                            var $p = $('.popover');
                            $p.each(function (i, el) {
                                $el = $(el);
                                $el.css('top', ($el.position().top - blockHeihgt) + 'px');
                            });
                        }
                    } else {
                        if ($container.hasClass('stick-sub')) {
                            $container.removeClass('stick-sub');
                            $block.hide();

                            var $p = $('.popover');
                            $p.each(function (i, el) {
                                $el = $(el);
                                $el.css('top', ($el.position().top + blockHeihgt) + 'px');
                            });
                        }
                    }
                    $container.show();
                } else {
                    $container.hide();
                    $block.show();
                }
            }.bind(this));

            var fields = this.getFields();

            var fieldInEditMode = null;
            for (var field in fields) {
                var fieldView = fields[field];
                this.listenTo(fieldView, 'edit', function (view) {
                    if (fieldInEditMode && fieldInEditMode.mode == 'edit') {
                        fieldInEditMode.inlineEditClose();
                    }
                    fieldInEditMode = view;
                }.bind(this));
            }
        },

        setEditMode: function () {
            this.$el.find('.record-buttons').addClass('hidden');
            this.$el.find('.edit-buttons').removeClass('hidden');

            var fields = this.getFields();
            for (var field in fields) {
                var fieldView = fields[field];
                if (!fieldView.readOnly) {
                    if (fieldView.mode == 'edit') {
                        fieldView.fetchToModel();
                        fieldView.removeInlineEditLinks();
                    }
                    fieldView.setMode('edit');
                    fieldView.render();
                }
            }
            this.mode = 'edit';
        },

        setDetailMode: function () {
            this.$el.find('.edit-buttons').addClass('hidden');
            this.$el.find('.record-buttons').removeClass('hidden');

            var fields = this.getFields();
            for (var field in fields) {
                var fieldView = fields[field];
                if (fieldView.mode != 'detail') {
                    fieldView.setMode('detail');
                    fieldView.render();
                }
            }
            this.mode = 'detail';
        },

        cancelEdit: function () {
            this.model.set(this.attributes);
            this.setDetailMode();
            this.setConfirmLayout(false);
        },

        delete: function () {
            if (confirm(this.translate('removeRecordConfirmation', 'messages'))) {
                this.trigger('before:delete');
                this.trigger('delete');

                this.notify('Removing...');

                var self = this;
                this.model.destroy({
                    wait: true,
                    error: function () {
                        self.notify('Error occured!', 'error');
                    },
                    success: function () {
                        self.notify('Removed', 'success');
                        self.trigger('after:delete');
                        self.exit('delete');
                    },
                });
            }
        },

        getFields: function () {
            var fields = _.clone(this.getView('record').nestedViews);
            if (this.hasView('side')) {
                _.extend(fields, this.getView('side').getFields());
            }
            return fields;
        },

        getFieldView: function (name) {
            var view = this.getView('record').getView(name) || null;
            if (!view && this.getView('side')) {
                view = (this.getView('side').getFields() || {})[name];
            }
            return view || null;
        },

        data: function () {
            return {
                scope: this.scope,
                buttonList: (typeof this.buttons === 'function') ? this.buttons() : this.buttonList,
                buttonEditList: this.buttonEditList,
                buttonsTop: this.buttonsPosition === 'both' || this.buttonsPosition === true || this.buttonsPosition === 'top',
                buttonsBottom: this.buttonsPosition === 'both' || this.buttonsPosition === true || this.buttonsPosition === 'bottom',
                name: this.name,
                id: this.id,
                isWide: this.isWide,
                isSmall: this.type == 'editSmall' || this.type == 'detailSmall'
            }
        },

        init: function () {
            this.scope = this.model.name;

            this.layoutName = this.options.layoutName || this.layoutName;

            this.type = this.options.type || this.type;

            this.buttons = this.options.buttons || this.buttons;
            this.buttonList = this.options.buttonList || this.buttonList;
            this.buttonList = _.clone(this.buttonList);

            this.returnUrl = this.options.returnUrl || this.returnUrl;
            this.exit = this.options.exit || this.exit;
            this.columnCount = this.options.columnCount || this.columnCount;

            Bull.View.prototype.init.call(this);
        },

        setup: function () {
            if (typeof this.model === 'undefined') {
                throw new Error('Model has not been injected into record view.');
            }

            this.on('remove', function () {
                this.setConfirmLayout(false);
            }, this);

            this.numId = Math.floor((Math.random() * 10000) + 1);
            this.id = Espo.Utils.toDom(this.scope) + '-' + Espo.Utils.toDom(this.type) + '-' + this.numId;

            if (_.isUndefined(this.events)) {
                this.events = {};
            }

            if ('buttonsPosition' in this.options) {
                this.buttonsPosition = this.options.buttonsPosition;
            }

            if ('isWide' in this.options) {
                this.isWide = this.options.isWide;
            }

            if ('sideView' in this.options) {
                this.sideView = this.options.sideView;
            }

            if ('bottomView' in this.options) {
                this.bottomView = this.options.bottomView;
            }

            if (!this.readOnly) {
                if ('readOnly' in this.options) {
                    this.readOnly = this.options.readOnly;
                }
            }

            if (!this.inlineEditDisabled) {
                if ('inlineEditDisabled' in this.options) {
                    this.inlineEditDisabled = this.options.inlineEditDisabled;
                }
            }

            if (this.model.isNew()) {
                this.isNew = true;
                this.removeButton('delete');
            }

            if (!this.getAcl().checkModel(this.model, 'edit')) {
                this.readOnly = true;
            }


            this.manageAccess();

            this.attributes = this.model.getClonedAttributes();

            this.build();
            this.listenTo(this.model, 'sync', function () {
                this.attributes = this.model.getClonedAttributes();
            }.bind(this));

            this.listenTo(this.model, 'change', function () {
                if (this.mode == 'edit') {
                    this.setConfirmLayout(true);
                }
            }, this);

            this.dependencyDefs = _.extend(this.getMetadata().get('clientDefs.' + this.model.name + '.formDependency') || {}, this.dependencyDefs);
            this._initDependancy();
        },


        afterSave: function () {
            if (this.isNew) {
                this.notify('Created', 'success');
            } else {
                this.notify('Saved', 'success');
            }
            this.enableButtons();
            this.setConfirmLayout(false);
        },

        beforeSave: function () {
            this.notify('Saving...');
        },

        afterSaveError: function () {
            this.enableButtons();
        },

        afterNotModified: function () {
            var msg = this.translate('notModified', 'messages');
            Espo.Ui.warning(msg, 'warning');
            this.enableButtons();
        },

        afterNotValid: function () {
            this.notify('Not valid', 'error');
            this.enableButtons();
        },

        showDuplicate: function (duplicates) {
            this.notify(false);
            this.createView('duplicate', 'Modals.Duplicate', {
                scope: this.scope,
                duplicates: duplicates,
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'save', function () {
                    this.model.set('forceDuplicate', true);
                    this.actionSave();
                }.bind(this));

            }.bind(this));
        },

        manageAccess: function () {
            if (this.readOnly) {
                this.removeButton('edit');
            }
            if (!this.getAcl().checkModel(this.model, 'delete')) {
                this.removeButton('delete');
            }
        },

        enableButtons: function () {
            this.$el.find(".button-container button").removeAttr('disabled');
        },

        disableButtons: function () {
            this.$el.find(".button-container button").attr('disabled', 'disabled');
        },

        removeButton: function (name) {
            for (var i in this.buttonList) {
                if (this.buttonList[i].name == name) {
                    this.buttonList.splice(i, 1);
                    break;
                }
            }
        },

        convertDetailLayout: function (simplifiedLayout) {
            var layout = [];

            var el = this.options.el || '#' + (this.id);

            for (var p in simplifiedLayout) {
                var panel = {};
                panel.label = simplifiedLayout[p].label || null;
                panel.name = simplifiedLayout[p].name || null;
                panel.rows = [];
                for (var i in simplifiedLayout[p].rows) {
                    var row = [];

                    for (var j in simplifiedLayout[p].rows[i]) {
                        var cellDefs = simplifiedLayout[p].rows[i][j];

                        if (cellDefs == false) {
                            row.push(false);
                            continue;
                        }

                        if (!cellDefs.name) {
                            continue;
                        }

                        var type = cellDefs.type || this.model.getFieldType(cellDefs.name) || 'base';
                        var viewName = cellDefs.view || this.model.getFieldParam(cellDefs.name, 'view') || this.getFieldManager().getViewName(type);

                        var o = {
                            el: el + ' .record .field-' + cellDefs.name,
                            defs: {
                                name: cellDefs.name,
                                params: cellDefs.params || {}
                            },
                            mode: this.fieldsMode
                        };

                        if (this.readOnly) {
                            o.readOnly = true;
                        } else {
                            if (cellDefs.readOnly) {
                                o.readOnly = true;
                            }
                        }

                        if (this.inlineEditDisabled || cellDefs.inlineEditDisabled) {
                            o.inlineEditDisabled = true;
                        }

                        var cell = {
                            name: cellDefs.name,
                            view: viewName,
                            el: el + ' .record .field-' + cellDefs.name,
                            fullWidth: cellDefs.fullWidth || false,
                            options: o
                        };

                        if ('customLabel' in cellDefs) {
                            cell.customLabel = cellDefs.customLabel;
                        }
                        if ('customCode' in cellDefs) {
                            cell.customCode = cellDefs.customCode;
                        }

                        row.push(cell);
                    }

                    panel.rows.push(row);
                }
                layout.push(panel);
            }
            return layout
        },

        getGridLayout: function (callback) {
            if (this.gridLayout !== null) {
                callback(this.gridLayout);
                return;
            }

            this._helper.layoutManager.get(this.model.name, this.layoutName, function (simpleLayout) {
                this.gridLayout = {
                    type: 'record',
                    layout: this.convertDetailLayout(simpleLayout),
                };
                callback(this.gridLayout);
            }.bind(this));
        },

        build: function (callback) {
            this.waitForView('record');

            var self = this;

            var el = this.options.el || '#' + (this.id);

            if (this.sideView) {
                this.createView('side', this.sideView, {
                    model: this.model,
                    el: el + ' .side',
                    type: this.type,
                    readOnly: this.readOnly,
                    inlineEditDisabled: this.inlineEditDisabled
                });
            }

            this.getGridLayout(function (layout) {
                this.createView('record', 'Base', {
                    model: this.model,
                    _layout: layout,
                    el: el + ' .record',
                    layoutData: {
                        model: this.model,
                        columnCount: this.columnCount,
                    },
                }, callback);
            }.bind(this));


            if (this.bottomView) {
                this.once('after:render', function () {
                    this.createView('bottom', this.bottomView, {
                        model: this.model,
                        el: el + ' .bottom',
                        notToRender: true,
                        readOnly: this.readOnly,
                        inlineEditDisabled: this.inlineEditDisabled
                    }, function (view) {
                        view.render();
                    }, false);
                }, this);

            }
        },


        /**
         * Called after save or cancel.
         * By default redirects page. Can be orverriden in options.
         * @param {String} after Name of action (save, cancel, etc.) after which #exit is invoked.
         */
        exit: function (after) {
            var url;
            if (this.returnUrl) {
                url = this.returnUrl;
            } else {
                url = '#' + this.model.name + '';
                if (after != 'delete' && this.model.id) {
                    url += '/view/' + this.model.id;
                }
            }
            this.getRouter().navigate(url, {trigger: true});
        },

        createField: function () {
        }

    });

});

