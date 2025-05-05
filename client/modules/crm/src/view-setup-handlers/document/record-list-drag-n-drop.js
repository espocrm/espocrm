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

import _ from 'underscore';
import {Events} from 'bullbone';

const Handler = function (view) {
    this.view = view;
};

_.extend(Handler.prototype, {

    process: function () {
        this.listenTo(this.view, 'after:render', () => this.initDragDrop());
        this.listenTo(this.view, 'remove', () => this.disable());
    },

    disable: function () {
        const $el = this.view.$el.parent();
        /** @type {Element} */
        const el = $el.get(0);

        $el.off('drop');

        if (!el) {
            return;
        }

        if (!this.onDragoverBind) {
            return;
        }

        el.removeEventListener('dragover', this.onDragoverBind);
        el.removeEventListener('dragenter', this.onDragenterBind);
        el.removeEventListener('dragleave', this.onDragleaveBind);
    },

    initDragDrop: function () {
        this.disable();

        const $el = this.view.$el.parent();
        const el = $el.get(0);

        $el.on('drop', e => {
            e.preventDefault();
            e.stopPropagation();

            e = e.originalEvent;

            if (
                e.dataTransfer &&
                e.dataTransfer.files &&
                e.dataTransfer.files.length === 1 &&
                this.dropEntered
            ) {
                this.removeDrop();

                this.create(e.dataTransfer.files[0]);

                return;
            }

            this.removeDrop($el);
        });


        this.dropEntered = false;

        this.onDragoverBind = this.onDragover.bind(this);
        this.onDragenterBind = this.onDragenter.bind(this);
        this.onDragleaveBind = this.onDragleave.bind(this);

        el.addEventListener('dragover', this.onDragoverBind);
        el.addEventListener('dragenter', this.onDragenterBind);
        el.addEventListener('dragleave', this.onDragleaveBind);
    },

    renderDrop: function () {
        this.dropEntered = true;

        const $backdrop =
            $('<div class="dd-backdrop">')
                .css('pointer-events', 'none')
                .append('<span class="fas fa-paperclip"></span>')
                .append(' ')
                .append(
                    $('<span>')
                        .text(this.view.getLanguage().translate('Create Document', 'labels', 'Document'))
                );

        this.view.$el.append($backdrop);
    },

    removeDrop: function () {
        this.view.$el.find('> .dd-backdrop').remove();

        this.dropEntered = false;
    },

    create: function (file) {
        this.view
            .actionQuickCreate()
            .then(view => {
                const fileView = view.getRecordView().getFieldView('file');

                if (!fileView) {
                    const msg = "No 'file' field on the layout.";

                    Espo.Ui.error(msg);
                    console.error(msg);

                    return;
                }

                if (fileView.isRendered()) {
                    fileView.uploadFile(file);

                    return;
                }

                this.listenToOnce(fileView, 'after:render', () => {
                    fileView.uploadFile(file);
                });
            });
    },

    /**
     * @param {DragEvent} e
     */
    onDragover: function (e) {
        e.preventDefault();
    },

    /**
     * @param {DragEvent} e
     */
    onDragenter: function (e) {
        e.preventDefault();

        if (!e.dataTransfer.types || !e.dataTransfer.types.length) {
            return;
        }

        if (!~e.dataTransfer.types.indexOf('Files')) {
            return;
        }

        if (!this.dropEntered) {
            this.renderDrop();
        }
    },

    /**
     * @param {DragEvent} e
     */
    onDragleave: function (e) {
        e.preventDefault();

        if (!this.dropEntered) {
            return;
        }

        let fromElement = e.fromElement || e.relatedTarget;

        if (
            fromElement &&
            $.contains(this.view.$el.parent().get(0), fromElement)
        ) {
            return;
        }

        if (
            fromElement &&
            fromElement.parentNode &&
            fromElement.parentNode.toString() === '[object ShadowRoot]'
        ) {
            return;
        }

        this.removeDrop();
    },
});

Object.assign(Handler.prototype, Events);

// noinspection JSUnusedGlobalSymbols
export default Handler;
