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

import TextFieldView from 'views/fields/text';
import {Textcomplete} from '@textcomplete/core';
import {TextareaEditor}  from '@textcomplete/textarea';

class NotePostFieldView extends TextFieldView {

    /**
     * @private
     * @type {Textcomplete}
     */
    textcomplete

    setup() {
        super.setup();

        this.insertedImagesData = {};

        this.addHandler('paste', 'textarea', (/** ClipboardEvent */event) => this.handlePaste(event));
    }

    onRemove() {
        super.onRemove();

        if (this.textcomplete) {
            this.textcomplete.destroy();
        }
    }

    /**
     * @return {HTMLTextAreaElement}
     */
    getTextAreaElement() {
        return this.$textarea.get(0);
    }

    /**
     * @private
     * @param {ClipboardEvent} event
     */
    handlePaste(event) {
        if (!event.clipboardData) {
            return;
        }

        let text = event.clipboardData.getData('text/plain');

        if (!text) {
            return;
        }

        text = text.trim();

        if (!text) {
            return;
        }

        this.handlePastedText(text);
    }

    afterRenderEdit() {
        const placeholderText = this.options.placeholderText ||
            this.translate('writeMessage', 'messages', 'Note');

        this.$element.attr('placeholder', placeholderText);

        this.$textarea = this.$element;

        const $textarea = this.$textarea;

        $textarea.off('drop');
        $textarea.off('dragover');
        $textarea.off('dragleave');

        this.$textarea.on('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();

            e = e.originalEvent;

            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                this.trigger('add-files', e.dataTransfer.files);
            }

            this.$textarea.attr('placeholder', originalPlaceholderText);
        });

        const originalPlaceholderText = this.$textarea.attr('placeholder');

        this.$textarea.on('dragover', e => {
            e.preventDefault();

            this.$textarea.attr('placeholder', this.translate('dropToAttach', 'messages'));
        });

        this.$textarea.on('dragleave', e => {
            e.preventDefault();

            this.$textarea.attr('placeholder', originalPlaceholderText);
        });

        this.initMentions();
    }

    initMentions() {
        const mentionPermissionLevel = this.getAcl().getPermissionLevel('mention');

        if (mentionPermissionLevel === 'no' /*|| this.model.isNew()*/) {
            return;
        }

        const maxSize = this.getConfig().get('recordsPerPage');

        const buildUserListUrl = term => {
            let url = `User?` +
                `${$.param({q: term})}` +
                `&${$.param({primaryFilter: 'active'})}` +
                `&orderBy=name` +
                `&maxSize=${maxSize}` +
                `&select=id,name,userName`;

            if (mentionPermissionLevel === 'team') {
                url += '&' + $.param({boolFilterList: ['onlyMyTeam']})
            }

            return url;
        };

        const editor = new TextareaEditor(this.textAreaElement);

        let bypass = false;

        this.textcomplete = new Textcomplete(
            editor,
            [
                {
                    match: /(^|\s)@(\w[\w@.-]*)$/,
                    index: 2,
                    search: (term, callback) => {
                        if (term.length === 0 || bypass) {
                            callback([]);

                            return;
                        }

                        Espo.Ajax.getRequest(buildUserListUrl(term))
                            .then(data => callback(data.list));
                    },
                    template: mention => {
                        const avatar = this.getHelper().getAvatarHtml(mention.id, 'medium', 16, 'avatar-link');
                        const name = this.getHelper().escapeString(mention.name);
                        const username = this.getHelper().escapeString(mention.userName);

                        return `${avatar + name} <span class="text-muted">@${username}</span>`;
                    },
                    replace: it => {
                        return '$1@' + it.userName + '';
                    },
                }
            ],
            {
                dropdown: {
                    item: {
                        className: "textcomplete-item",
                        activeClassName: "textcomplete-item active",
                    },
                    style: {
                        zIndex: '1100',
                    },
                }
            }
        );

        this.textcomplete.on('select', () => {
            bypass = true;

            setTimeout(() => {
                bypass = false;
            }, 100);
        });

        this.textAreaElement.addEventListener('blur', () => {
            bypass = true;

            setTimeout(() => {
                bypass = false;
            }, 200);

            setTimeout(() => {
                this.textcomplete?.hide();
            }, 150);
        });
    }

    validateRequired() {
        if (this.isRequired()) {
            if ((this.model.get('attachmentsIds') || []).length) {
                return false;
            }
        }

        return super.validateRequired();
    }

    /**
     * @private
     * @param {string} text
     */
    handlePastedText(text) {
        // noinspection RegExpRedundantEscape,RegExpSimplifiable
        if (!(/^http(s){0,1}\:\/\//.test(text))) {
            return;
        }

        const field = 'attachments';

        const imageExtensionList = ['jpg', 'jpeg', 'png', 'gif'];
        const regExpString = '.+\\.(' + imageExtensionList.join('|') + ')(/?.*){0,1}$';
        const regExp = new RegExp(regExpString, 'i');
        let url = text;
        const siteUrl = this.getConfig().get('siteUrl').replace(/\/$/, '');

        const setIds = /** @type {string[]} */this.model.attributes[`${field}Ids`] || [];

        if (regExp.test(text)) {
            const insertedId = this.insertedImagesData[url];

            if (insertedId && setIds.includes(insertedId)) {
                return;
            }

            Espo.Ajax
                .postRequest('Attachment/fromImageUrl', {
                    url: url,
                    parentType: this.model.entityType,
                    field: field,
                })
                .then(/** {id: string, name: string, type: string} */result => {
                    const ids = [...(this.model.attributes[`${field}Ids`] || [])];
                    const names = {...this.model.attributes[`${field}Names`]};
                    const types = {...this.model.attributes[`${field}Types`]};

                    ids.push(result.id);
                    names[result.id] = result.name;
                    types[result.id] = result.type;

                    this.insertedImagesData[url] = result.id;

                    this.model.set({
                        [`${field}Ids`]: ids,
                        [`${field}Names`]: names,
                        [`${field}Types`]: types,
                    });
                })
                .catch(xhr => {
                    xhr.errorIsHandled = true;
                });

            return;
        }

        // noinspection RegExpRedundantEscape
        if (/\?entryPoint\=image\&/.test(text) && text.indexOf(siteUrl) === 0) {
            // noinspection RegExpRedundantEscape,RegExpSimplifiable
            url = text.replace(/[\&]{0,1}size\=[a-z\-]*/, '');

            // noinspection RegExpRedundantEscape,RegExpSimplifiable
            const match = /\&{0,1}id\=([a-z0-9A-Z]*)/g.exec(text);

            if (match.length !== 2) {
                return;
            }

            const id = match[1];

            if (setIds.includes(id)) {
                return;
            }

            const insertedId = this.insertedImagesData[id];

            if (insertedId && setIds.includes(insertedId)) {
                return;
            }

            Espo.Ajax
                .postRequest(`Attachment/copy/${id}`, {
                    parentType: this.model.entityType,
                    field: field,
                })
                .then(/** {id: string, name: string, type: string} */result => {
                    const ids = [...(this.model.attributes[`${field}Ids`] || [])];
                    const names = {...this.model.attributes[`${field}Names`]};
                    const types = {...this.model.attributes[`${field}Types`]};

                    ids.push(result.id);
                    names[result.id] = result.name;
                    types[result.id] = result.type;

                    this.insertedImagesData[id] = result.id;

                    this.model.set({
                        [`${field}Ids`]: ids,
                        [`${field}Names`]: names,
                        [`${field}Types`]: types,
                    });
                })
                .catch(xhr => {
                    xhr.errorIsHandled = true;
                });
        }
    }
}

export default NotePostFieldView;
