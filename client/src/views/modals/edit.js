/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

/** @module views/modals/edit */

import ModalView from 'views/modal';
import Backbone from 'backbone';

/**
 * A quick edit modal.
 */
class EditModalView extends ModalView {

    template = 'modals/edit'

    cssName = 'edit-modal'
    /** @protected */
    saveDisabled = false
    /** @protected */
    fullFormDisabled = false
    /** @protected */
    editView = null
    escapeDisabled = true
    className = 'dialog dialog-record'
    /** @protected */
    sideDisabled = false
    /** @protected */
    bottomDisabled = false

    isCollapsible = true

    /**
     * @private
     * @type {boolean}
     */
    wasModified = false

    /**
     * @private
     * @type {string}
     */
    nameAttribute

    shortcutKeys = {
        /** @this EditModalView */
        'Control+Enter': function (e) {
            if (this.saveDisabled) {
                return;
            }

            if (this.buttonList.findIndex(item => item.name === 'save' && !item.hidden) === -1) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            if (document.activeElement instanceof HTMLInputElement) {
                // Fields may need to fetch data first.
                document.activeElement.dispatchEvent(new Event('change', {bubbles: true}));
            }

            this.actionSave();
        },
        /** @this EditModalView */
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
        /** @this EditModalView */
        'Escape': function (e) {
            if (this.saveDisabled) {
                return;
            }

            e.stopPropagation();
            e.preventDefault();

            const focusedFieldView = this.getRecordView().getFocusedFieldView();

            if (focusedFieldView) {
                this.model.set(focusedFieldView.fetch(), {skipReRender: true});
            }

            if (this.getRecordView().isChanged) {
                this.confirm(this.translate('confirmLeaveOutMessage', 'messages'))
                    .then(() => this.actionClose());

                return;
            }

            this.actionClose();
        },
        /** @this EditModalView */
        'Control+Backslash': function (e) {
            this.getRecordView().handleShortcutKeyControlBackslash(e);
        },
    }

    /**
     * @typedef {Record} module:views/modals/edit~options
     *
     * @property {string} entityType An entity type.
     * @property {string} [id] An ID.
     * @property {string} [layoutName] A layout name.
     * @property {Record} [attributes] Attributes.
     * @property {model:model~setRelateItem | model:model~setRelateItem[]} [relate] A relate data.
     * @property {import('view-record-helper')} [recordHelper] A record helper.
     * @property {boolean} [saveDisabled] Disable save.
     * @property {boolean} [fullFormDisabled] Disable full-form.
     * @property {string} [headerText] A header text.
     * @property {boolean} [focusForCreate] Focus for create.
     * @property {string} [rootUrl] A root URL.
     * @property {string} [returnUrl] A return URL.
     * @property {{
     *     controller: string,
     *     action: string|null,
     *     options: {isReturn?: boolean} & Record,
     * }} [returnDispatchParams] Return dispatch params.
     * @property {string} [fullFormUrl] A full-form URL. As of v9.0.
     */

    /**
     * @param {module:views/modals/edit~options} options
     */
    constructor(options) {
        super(options);
    }

    setup() {
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
                onClick: () => this.actionSave(),
            });
        }

        this.fullFormDisabled = this.options.fullFormDisabled || this.fullFormDisabled;

        this.layoutName = this.options.layoutName || this.layoutName;

        if (!this.fullFormDisabled) {
            this.buttonList.push({
                name: 'fullForm',
                label: 'Full Form',
                onClick: () => this.actionFullForm(),
            });
        }

        this.buttonList.push({
            name: 'cancel',
            label: 'Cancel',
            title: 'Esc',
        });

        this.scope = this.scope || this.options.scope || this.options.entityType;
        this.entityType = this.options.entityType || this.scope;
        this.id = this.options.id;

        this.nameAttribute = this.getMetadata().get(`clientDefs.${this.entityType}.nameAttribute`) || 'name';

        if (this.options.headerText !== undefined) {
            this.headerHtml = undefined;
            this.headerText = this.options.headerText;
        }

        this.sourceModel = this.model;

        this.waitForView('edit');

        this.getModelFactory().create(this.entityType, model => {
            if (this.id) {
                if (this.sourceModel) {
                    model = this.model = this.sourceModel.clone();
                } else {
                    this.model = model;

                    model.id = this.id;
                }

                model.fetch()
                    .then(() => {
                        if (!this.headerText) {
                            this.headerHtml = this.composeHeaderHtml();
                        }

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

            if (!this.headerText) {
                this.headerHtml = this.composeHeaderHtml();
            }

            this.createRecordView(model);
        });

        this.listenTo(this.model, 'change', (m, o) => {
            if (o.ui) {
                this.wasModified = true;
            }
        });
    }

    /**
     * @param {module:model} model
     * @param {function} [callback]
     */
    createRecordView(model, callback) {
        const viewName =
            this.editView ||
            this.getMetadata().get(['clientDefs', model.entityType, 'recordViews', 'editSmall']) ||
            this.getMetadata().get(['clientDefs', model.entityType, 'recordViews', 'editQuick']) ||
            'views/record/edit-small';

        const options = {
            model: model,
            fullSelector: this.containerSelector + ' .edit-container',
            type: 'editSmall',
            layoutName: this.layoutName || 'detailSmall',
            buttonsDisabled: true,
            sideDisabled: this.sideDisabled,
            bottomDisabled: this.bottomDisabled,
            focusForCreate: this.options.focusForCreate,
            recordHelper: this.options.recordHelper,
            webSocketDisabled: true,
            exit: () => {},
        };

        this.handleRecordViewOptions(options);

        this.createView('edit', viewName, options, callback)
            .then(/** import('views/fields/base').default */view => {
                this.listenTo(view, 'before:save', () => this.trigger('before:save', model));

                if (this.options.relate && ('link' in this.options.relate)) {
                    const link = this.options.relate.link;

                    if (
                        model.hasField(link) &&
                        ['link'].includes(model.getFieldType(link))
                    ) {
                        view.setFieldReadOnly(link);
                    }
                }
            });
    }

    handleRecordViewOptions(options) {}

    /**
     * @return {module:views/record/edit}
     */
    getRecordView() {
        return this.getView('edit');
    }

    onBackdropClick() {
        if (this.getRecordView().isChanged) {
            return;
        }

        this.close();
    }

    /**
     * @protected
     * @return {string}
     */
    composeHeaderHtml() {
        let html;

        if (!this.id) {
            html = $('<span>')
                .text(this.getLanguage().translate('Create ' + this.scope, 'labels', this.scope))
                .get(0).outerHTML;
        } else {
            const wrapper = document.createElement('span');

            const scope = document.createElement('span');
            scope.textContent = this.getLanguage().translate(this.scope, 'scopeNames');

            const separator = document.createElement('span');
            separator.classList.add('chevron-right');

            const name = this.model.attributes[this.nameAttribute];

            wrapper.append(
                document.createTextNode(this.getLanguage().translate('Edit') + ' · '),
                scope
            );

            if (name) {
                wrapper.append(
                    ' ',
                    separator,
                    ' ',
                    name
                );
            }

            html = wrapper.outerHTML;
        }

        if (!this.fullFormDisabled) {
            const url = this.id ?
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
    }

    /**
     * @protected
     * @param {{bypassClose?: boolean}} [data]
     */
    actionSave(data = {}) {
        const editView = this.getRecordView();

        const model = editView.model;

        const $buttons = this.dialog.$el.find('.modal-footer button');

        $buttons.addClass('disabled').attr('disabled', 'disabled');

        editView
            .save()
            .then(() => {
                const wasNew = !this.id;

                if (wasNew) {
                    this.id = model.id;
                }

                this.trigger('after:save', model, {bypassClose: data.bypassClose});

                if (!data.bypassClose) {
                    this.dialog.close();

                    if (wasNew) {
                        const url = `#${this.scope}/view/${model.id}`;
                        const name = model.attributes[this.nameAttribute] || this.model.id;

                        const msg = this.translate('Created') + '\n' +
                            `[${name}](${url})`;

                        Espo.Ui.notify(msg, 'success', 4000, {suppress: true});
                    }

                    return;
                }

                $(this.containerElement).find('.modal-header .modal-title-text')
                    .html(this.composeHeaderHtml());

                $buttons.removeClass('disabled').removeAttr('disabled');
            })
            .catch(() => {
                $buttons.removeClass('disabled').removeAttr('disabled');
            })
    }

    actionSaveAndContinueEditing() {
        this.actionSave({bypassClose: true});
    }

    actionFullForm() {
        let url;
        const router = this.getRouter();

        let attributes;
        let model;
        let options;

        if (!this.id) {
            url = this.options.fullFormUrl || `#${this.scope}/create`;

            attributes = this.getRecordView().fetch();
            model = this.getRecordView().model;

            attributes = {...attributes, ...model.getClonedAttributes()};

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
            url = this.options.fullFormUrl || `#${this.scope}/edit/${this.id}`;

            attributes = this.getRecordView().fetch();
            model = this.getRecordView().model;

            attributes = {...attributes, ...model.getClonedAttributes()};

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
    }

    async beforeCollapse() {
        if (this.wasModified) {
            this.getRecordView().setConfirmLeaveOut(false);
            this.getRouter().addWindowLeaveOutObject(this);
        }
    }

    afterExpand() {
        if (this.wasModified) {
            this.getRecordView().setConfirmLeaveOut(true);
        }

        this.getRouter().removeWindowLeaveOutObject(this);
    }
}

export default EditModalView;
