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

define('crm:views/meeting/modals/detail', 'views/modals/detail', function (Dep) {

    return Dep.extend({

        setupAfterModelCreated: function () {
            Dep.prototype.setupAfterModelCreated.call(this);

            var buttonData = this.getAcceptanceButtonData();

            this.addButton({
                name: 'setAcceptanceStatus',
                html: buttonData.html,
                hidden: this.hasAcceptanceStatusButton(),
                style: buttonData.style,
                pullLeft: true,
            }, 'cancel');

            if (
                !~this.getAcl().getScopeForbiddenFieldList(this.model.entityType).indexOf('status')
            ) {
                this.addDropdownItem({
                    name: 'setHeld',
                    html: this.translate('Set Held', 'labels', this.model.entityType),
                    hidden: true,
                });
                this.addDropdownItem({
                    name: 'setNotHeld',
                    html: this.translate('Set Not Held', 'labels', this.model.entityType),
                    hidden: true,
                });
            }

            this.initAcceptenceStatus();
            this.on('switch-model', function (model, previousModel) {
                this.stopListening(previousModel, 'sync');
                this.initAcceptenceStatus();
            }, this);

             this.on('after:save', function () {
                if (this.hasAcceptanceStatusButton()) {
                    this.showAcceptanceButton();
                } else {
                    this.hideAcceptanceButton();
                }
            }, this);
        },

        controlRecordButtonsVisibility: function () {
            Dep.prototype.controlRecordButtonsVisibility.call(this);
            this.controlStatusActionVisibility();
        },

        controlStatusActionVisibility: function () {
            if (this.getAcl().check(this.model, 'edit') && !~['Held', 'Not Held'].indexOf(this.model.get('status'))) {
                this.showActionItem('setHeld');
                this.showActionItem('setNotHeld');
            } else {
                this.hideActionItem('setHeld');
                this.hideActionItem('setNotHeld');
            }
        },

        hasSetStatusButton: function () {

        },

        initAcceptenceStatus: function () {
            if (this.hasAcceptanceStatusButton()) {
                this.showAcceptanceButton();
            } else {
                this.hideAcceptanceButton();
            }

            this.listenTo(this.model, 'sync', function () {
                if (this.hasAcceptanceStatusButton()) {
                    this.showAcceptanceButton();
                } else {
                    this.hideAcceptanceButton();
                }
            }, this);
        },

        getAcceptanceButtonData: function () {
            var acceptanceStatus = this.model.getLinkMultipleColumn('users', 'status', this.getUser().id);

            var html;
            var style = 'default';
            if (acceptanceStatus && acceptanceStatus !== 'None') {
                html = this.getLanguage().translateOption(acceptanceStatus, 'acceptanceStatus', this.model.entityType);
                style = this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'style', acceptanceStatus]);
            } else {
                html = this.translate('Acceptance', 'labels', 'Meeting');
            }

            return {
                style: style,
                html: html
            };
        },

        showAcceptanceButton: function () {
            this.showButton('setAcceptanceStatus');

            if (!this.isRendered()) {
                this.once('after:render', this.showAcceptanceButton, this);
                return;
            }

            var data = this.getAcceptanceButtonData();

            var $button = this.$el.find('.modal-footer [data-name="setAcceptanceStatus"]');

            $button.html(data.html);

            $button.removeClass('btn-default');
            $button.removeClass('btn-success');
            $button.removeClass('btn-warning');
            $button.removeClass('btn-info');
            $button.removeClass('btn-primary');
            $button.removeClass('btn-danger');
            $button.addClass('btn-' + data.style);
        },

        hideAcceptanceButton: function () {
            this.hideButton('setAcceptanceStatus');
        },

        hasAcceptanceStatusButton: function () {
            if (!this.model.has('status')) return;
            if (!this.model.has('usersIds')) return;

            if (~['Held', 'Not Held'].indexOf(this.model.get('status'))) {
                return;
            }

            if (!~this.model.getLinkMultipleIdList('users').indexOf(this.getUser().id)) {
                return;
            }

            var acceptanceStatus = this.model.getLinkMultipleColumn('users', 'status', this.getUser().id);

            var html;
            var style = 'default';
            if (acceptanceStatus && acceptanceStatus !== 'None') {
                html = this.getLanguage().translateOption(acceptanceStatus, 'acceptanceStatus', this.model.entityType);
                style = this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'style', acceptanceStatus]);
            } else {
                html = this.translate('Acceptance', 'labels', 'Meeting');
            }

            return true;
        },

        actionSetAcceptanceStatus: function () {
            var acceptanceStatus = this.model.getLinkMultipleColumn('users', 'status', this.getUser().id);

            this.createView('dialog', 'crm:views/meeting/modals/acceptance-status', {
                model: this.model
            }, function (view) {
                view.render();

                this.listenTo(view, 'set-status', function (status) {
                    this.hideAcceptanceButton();
                    Espo.Ajax.postRequest(this.model.entityType + '/action/setAcceptanceStatus', {
                        id: this.model.id,
                        status: status
                    }).then(function () {
                        this.model.fetch();
                    }.bind(this));
                });
            });
        },

        actionSetHeld: function () {
            this.model.save({status: 'Held'});
            this.trigger('after:save', this.model);
        },

        actionSetNotHeld: function () {
            this.model.save({status: 'Not Held'});
            this.trigger('after:save', this.model);
        },
    });
});
