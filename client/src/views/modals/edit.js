/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/modals/edit', ['views/modal'], function (Dep) {

    /**
     * A quick edit modal.
     *
     * @class
     * @name Class
     * @memberOf module:views/modals/edit
     * @extends module:views/modal.Class
     */
    return Dep.extend(/** @lends module:views/modals/edit.Class# */{

        cssName: 'edit-modal',

        template: 'modals/edit',

        saveDisabled: false,

        fullFormDisabled: false,

        editView: null,

        escapeDisabled: true,

        fitHeight: true,

        className: 'dialog dialog-record',

        sideDisabled: false,

        bottomDisabled: false,

        shortcutKeys: {
            'Control+Enter': function (e) {
                if (this.saveDisabled) {
                    return;
                }

                if (this.buttonList.findIndex(item => item.name === 'save' && !item.hidden) === -1) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                this.actionSave();
            },
            'Control+KeyS': function (e) {
                if (this.saveDisabled) {
                    return;
                }

                if (this.buttonList.findIndex(item => item.name === 'save' && !item.hidden) === -1) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                this.actionSaveAndContinueEditing();
            },
            'Escape': function (e) {
                if (this.saveDisabled) {
                    return;
                }

                e.stopPropagation();
                e.preventDefault();

                let focusedFieldView = this.getRecordView().getFocusedFieldView();

                if (focusedFieldView) {
                    this.model.set(focusedFieldView.fetch());
                }

                if (this.getRecordView().isChanged) {
                    this.confirm(this.translate('confirmLeaveOutMessage', 'messages'))
                        .then(() => this.actionClose());

                    return;
                }

                this.actionClose();
            },
            'Control+Backslash': function (e) {
                this.getRecordView().handleShortcutKeyControlBackslash(e);
            },
        },

        setup: function () {
            this.buttonList = [];

            if ('saveDisabled' in this.options) {
                this.saveDisabled = this.options.saveDisabled;
            }

            if (!this.saveDisabled) {
                this.buttonList.push({
                    name: 'save',
                    label: 'Save',
                    style: 'primary',
                    title: 'Ctrl+Enter',
                });
            }

            this.fullFormDisabled = this.options.fullFormDisabled || this.fullFormDisabled;

            this.layoutName = this.options.layoutName || this.layoutName;

            if (!this.fullFormDisabled) {
                this.buttonList.push({
                    name: 'fullForm',
                    label: 'Full Form',
                });
            }

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel',
                title: 'Esc',
            });

            this.scope = this.scope || this.options.scope;

            this.entityType = this.options.entityType || this.scope;

            this.id = this.options.id;

            this.headerHtml = this.composeHeaderHtml();

            this.sourceModel = this.model;

            this.waitForView('edit');

            this.getModelFactory().create(this.entityType, (model) => {
                if (this.id) {
                    if (this.sourceModel) {
                        model = this.model = this.sourceModel.clone();
                    }
                    else {
                        this.model = model;

                        model.id = this.id;
                    }

                    model
                        .fetch()
                        .then(() => {
                            this.createRecordView(model);
                        });

                    return;
                }

                this.model = model;

                if (this.options.relate) {
                    model.setRelate(this.options.relate);
                }

                if (this.options.attributes) {
                    model.set(this.options.attributes);
                }

                this.createRecordView(model);
            });
        },

        createRecordView: function (model, callback) {
            var viewName =
                this.editViewName ||
                this.editView ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'editSmall']) ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'editQuick']) ||
                'views/record/edit-small';

            let options = {
                model: model,
                el: this.containerSelector + ' .edit-container',
                type: 'editSmall',
                layoutName: this.layoutName || 'detailSmall',
                buttonsDisabled: true,
                sideDisabled: this.sideDisabled,
                bottomDisabled: this.bottomDisabled,
                focusForCreate: this.options.focusForCreate,
                exit: function () {},
            };

            this.handleRecordViewOptions(options);

            this.createView('edit', viewName, options, callback)
                .then(view => {
                    this.listenTo(view, 'before:save', () => this.trigger('before:save', model));
                });
        },

        handleRecordViewOptions: function (options) {},

        /**
         * @return {module:views/record/edit.Class}
         */
        getRecordView: function () {
            return this.getView('edit');
        },

        onBackdropClick: function () {
            if (this.getRecordView().isChanged) {
                return;
            }

            this.close();
        },

        /**
         * @protected
         * @return {string}
         */
        composeHeaderHtml: function () {
            let html;

            if (!this.id) {
                html = $('<span>')
                    .text(this.getLanguage().translate('Create ' + this.scope, 'labels', this.scope))
                    .get(0).outerHTML;
            }
            else {
                let text = this.getLanguage().translate('Edit') + ': ' +
                    this.getLanguage().translate(this.scope, 'scopeNames');

                html = $('<span>')
                    .text(text)
                    .get(0).outerHTML;
            }

            if (!this.fullFormDisabled) {
                let url = this.id ?
                    '#' + this.scope + '/edit/' + this.id :
                    '#' + this.scope + '/create';

                html =
                    $('<a>')
                        .attr('href', url)
                        .addClass('action')
                        .attr('title', this.translate('Full Form'))
                        .attr('data-action', 'fullForm')
                        .append(html)
                        .get(0).outerHTML;
            }

            html = this.getHelper().getScopeColorIconHtml(this.scope) + html;

            return html;
        },

        actionSave: function (data) {
            data = data || {};

            let editView = this.getRecordView();

            let model = editView.model;

            let $buttons = this.dialog.$el.find('.modal-footer button');

            $buttons.addClass('disabled').attr('disabled', 'disabled');

            editView
                .save()
                .then(() => {
                    let wasNew = !this.id;

                    if (wasNew) {
                        this.id = model.id;
                    }

                    this.trigger('after:save', model, {bypassClose: data.bypassClose});

                    if (!data.bypassClose) {
                        this.dialog.close();

                        return;
                    }

                    this.$el.find('.modal-header .modal-title-text')
                        .html(this.composeHeaderHtml());

                    $buttons.removeClass('disabled').removeAttr('disabled');
                })
                .catch(() => {
                    $buttons.removeClass('disabled').removeAttr('disabled');
                })
        },

        actionSaveAndContinueEditing: function () {
            this.actionSave({bypassClose: true});
        },

        actionFullForm: function (dialog) {
            var url;
            var router = this.getRouter();

            var attributes;
            var model;
            var options;

            if (!this.id) {
                url = '#' + this.scope + '/create';

                attributes = this.getView('edit').fetch();
                model = this.getView('edit').model;

                attributes = _.extend(attributes, model.getClonedAttributes());

                options = {
                    attributes: attributes,
                    relate: this.options.relate,
                    returnUrl: this.options.returnUrl || Backbone.history.fragment,
                    returnDispatchParams: this.options.returnDispatchParams || null,
                };

                if (this.options.rootUrl) {
                    options.rootUrl = this.options.rootUrl;
                }

                setTimeout(() => {
                    router.dispatch(this.scope, 'create', options);
                    router.navigate(url, {trigger: false});
                }, 10);
            }
            else {
                url = '#' + this.scope + '/edit/' + this.id;

                attributes = this.getView('edit').fetch();
                model = this.getView('edit').model;

                attributes = _.extend(attributes, model.getClonedAttributes());

                options = {
                    attributes: attributes,
                    returnUrl: this.options.returnUrl || Backbone.history.fragment,
                    returnDispatchParams: this.options.returnDispatchParams || null,
                    model: this.sourceModel,
                    id: this.id,
                };

                if (this.options.rootUrl) {
                    options.rootUrl = this.options.rootUrl;
                }

                setTimeout(() => {
                    router.dispatch(this.scope, 'edit', options);
                    router.navigate(url, {trigger: false});
                }, 10);
            }

            this.trigger('leave');
            this.dialog.close();
        },
    });
});

