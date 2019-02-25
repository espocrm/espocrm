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

Espo.define('crm:views/calendar/modals/edit', 'views/modals/edit', function (Dep) {

    return Dep.extend({

        template: 'crm:calendar/modals/edit',

        scopeList: [
            'Meeting',
            'Call',
            'Task',
        ],

        data: function () {
            return {
                scopeList: this.scopeList,
                scope: this.scope,
                isNew: !(this.id)
            };
        },

        events: {
            'change .scope-switcher input[name="scope"]': function () {
                this.notify('Loading...');
                var scope = $('.scope-switcher input[name="scope"]:checked').val();
                this.scope = scope;
                this.getModelFactory().create(this.scope, function (model) {
                    model.populateDefaults();
                    var attributes = this.getView('edit').fetch();
                    attributes = _.extend(attributes, this.getView('edit').model.toJSON());
                    model.set(attributes);
                    this.model = model;
                    this.createRecordView(model, function (view) {
                        view.render();
                        view.notify(false);
                    });
                    this.handleAccess(model);
                }.bind(this));
            },
        },

        createRecordView: function (model, callback) {
            if (!this.id && !this.dateIsChanged) {
                if (this.options.dateStart && this.options.dateEnd) {
                    this.model.set('dateStart', this.options.dateStart);
                    this.model.set('dateEnd', this.options.dateEnd);
                }

                if (this.options.allDay) {
                    var allDayScopeList = this.getMetadata().get('clientDefs.Calendar.allDayScopeList') || [];
                    if (~allDayScopeList.indexOf(this.scope)) {
                        this.model.set('dateStart', null);
                        this.model.set('dateEnd', null);
                        this.model.set('dateStartDate', null);
                        this.model.set('dateEndDate', this.options.dateEndDate);
                        if (this.options.dateEndDate !== this.options.dateStartDate) {
                            this.model.set('dateStartDate', this.options.dateStartDate)
                        }
                    } else if (this.getMetadata().get(['entityDefs', this.scope, 'fields', 'dateStartDate'])) {
                        this.model.set('dateStart', null);
                        this.model.set('dateEnd', null);
                        this.model.set('dateStartDate', this.options.dateStartDate);
                        this.model.set('dateEndDate', this.options.dateEndDate);
                        this.model.set('isAllDay', true);
                    }
                }
            }

            this.listenTo(this.model, 'change:dateStart', function (m, value, o) {
                if (o.ui) {
                    this.dateIsChanged = true;
                }
            }, this);
            this.listenTo(this.model, 'change:dateEnd', function (m, value, o) {
                if (o.ui || o.updatedByDuration) {
                    this.dateIsChanged = true;
                }
            }, this);

            Dep.prototype.createRecordView.call(this, model, callback);
        },

        handleAccess: function (model) {
            if (this.id && !this.getAcl().checkModel(model, 'edit') || !this.id && !this.getAcl().checkModel(model, 'create')) {
                this.hideButton('save');
                this.hideButton('fullForm');
                this.$el.find('button[data-name="save"]').addClass('hidden');
                this.$el.find('button[data-name="fullForm"]').addClass('hidden');
            } else {
                this.showButton('save');
                this.showButton('fullForm');
            }

            if (!this.getAcl().checkModel(model, 'delete')) {
                this.hideButton('remove');
            } else {
                this.showButton('remove');
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.hasView('edit')) {
                var model = this.getView('edit').model;
                if (model) {
                    this.handleAccess(model);
                }
            }
        },

        setup: function () {
            this.scopeList = Espo.Utils.clone(this.options.scopeList || this.scopeList);
            this.enabledScopeList = this.options.enabledScopeList || this.scopeList;

            if (!this.options.id && !this.options.scope) {
                var scopeList = [];
                this.scopeList.forEach(function (scope) {
                    if (this.getAcl().check(scope, 'create')) {
                        if (~this.enabledScopeList.indexOf(scope)) {
                            scopeList.push(scope);
                        }
                    }
                }, this);
                this.scopeList = scopeList;

                var calendarDefaultEntity = scopeList[0];

                if (calendarDefaultEntity && ~this.scopeList.indexOf(calendarDefaultEntity)) {
                    this.options.scope = calendarDefaultEntity;
                } else {
                    this.options.scope = this.scopeList[0] || null;
                }

                if (this.scopeList.length == 0) {
                    this.remove();
                    return;
                }
            }
            Dep.prototype.setup.call(this);

            if (!this.id) {
                this.headerHtml = this.translate('Create', 'labels', 'Calendar');
            }

            if (this.id) {
                this.buttonList.splice(1, 0, {
                    name: 'remove',
                    text: this.translate('Remove')
                });
            }
        },

        actionRemove: function () {
            var model = this.getView('edit').model;

            this.confirm(this.translate('removeRecordConfirmation', 'messages'), function () {
                var $buttons = this.dialog.$el.find('.modal-footer button');
                $buttons.addClass('disabled');
                model.destroy({
                    success: function () {
                        this.trigger('after:destroy', model);
                        this.dialog.close();
                    }.bind(this),
                    error: function () {
                        $buttons.removeClass('disabled');
                    }
                });
            }, this);
        }
    });
});

