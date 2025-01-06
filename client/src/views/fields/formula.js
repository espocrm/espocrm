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

/**
 * @type {{
 *     edit: import('ace-builds').edit,
 *     require: import('ace-builds').require,
 * }}
 */
let ace;

class FormulaFieldView extends TextFieldView {

    detailTemplate ='fields/formula/detail'
    editTemplate = 'fields/formula/edit'

    height = 308
    maxLineDetailCount = 80
    maxLineEditCount = 200
    insertDisabled = false
    checkSyntaxDisabled = false
    smallFont = false

    events = {
        /** @this FormulaFieldView */
        'click [data-action="addAttribute"]': function () {
            this.addAttribute();
        },
        /** @this FormulaFieldView */
        'click [data-action="addFunction"]': function () {
            this.addFunction();
        },
        /** @this FormulaFieldView */
        'click [data-action="checkSyntax"]': function () {
            this.checkSyntax();
        },
    }

    setup() {
        super.setup();

        this.height = this.options.height || this.params.height || this.height;
        this.smallFont = this.options.smallFont || this.params.smallFont || this.smallFont;

        this.maxLineDetailCount =
            this.options.maxLineDetailCount ||
            this.params.maxLineDetailCount ||
            this.maxLineDetailCount;

        this.maxLineEditCount =
            this.options.maxLineEditCount ||
            this.params.maxLineEditCount ||
            this.maxLineEditCount;

        this.targetEntityType =
            this.options.targetEntityType ||
            this.params.targetEntityType ||
            this.targetEntityType;

        this.insertDisabled = this.insertDisabled || this.options.insertDisabled;
        this.checkSyntaxDisabled = this.checkSyntaxDisabled || this.options.checkSyntaxDisabled;

        this.containerId = 'editor-' + Math.floor((Math.random() * 10000) + 1).toString();

        if (this.mode === this.MODE_EDIT || this.mode === this.MODE_DETAIL) {
            this.wait(
                this.requireAce()
            );
        }

        this.on('remove', () => {
            if (this.editor) {
                this.editor.destroy();
            }
        });
    }

    requireAce() {
        return Espo.loader.requirePromise('lib!ace')
            .then(lib => {
                ace = /** window.ace */lib;

                const list = [
                    Espo.loader.requirePromise('lib!ace-mode-javascript'),
                    Espo.loader.requirePromise('lib!ace-ext-language_tools'),
                ];

                if (this.getThemeManager().getParam('isDark')) {
                    list.push(
                        Espo.loader.requirePromise('lib!ace-theme-tomorrow_night')
                    );
                }

                return Promise.all(list);
            });
    }

    data() {
        const data = super.data();

        data.containerId = this.containerId;
        data.targetEntityType = this.targetEntityType;
        data.hasSide = !this.insertDisabled || !this.checkSyntaxDisabled;
        data.hasInsert = !this.insertDisabled;
        data.hasCheckSyntax = !this.checkSyntaxDisabled;

        return data;
    }

    afterRender() {
        super.afterRender();

        this.$editor = this.$el.find('#' + this.containerId);

        if (
            this.$editor.length &&
            (
                this.mode === this.MODE_EDIT ||
                this.mode === this.MODE_DETAIL ||
                this.mode === this.MODE_LIST
            )
        ) {
            const fontSize = this.smallFont ?
                'var(--font-size-small)' :
                'var(--font-size-base)';

            const lineHeight = this.smallFont ?
                'var(--line-height-small)' :
                'var(--line-height-computed)';

            this.$editor.css('fontSize', fontSize);

            if (this.mode === this.MODE_EDIT) {
                const height = (this.height * this.getThemeManager().getFontSizeFactor()).toString();

                this.$editor.css('minHeight', `${height}px`);
            }

            const editor = this.editor = ace.edit(this.containerId);

            editor.setOptions({fontFamily: 'var(--font-family-monospace)'});
            editor.setFontSize(fontSize);
            editor.container.style.lineHeight = lineHeight;
            editor.renderer.updateFontSize();

            editor.setOptions({
                maxLines: this.mode === this.MODE_EDIT ?
                    this.maxLineEditCount :
                    this.maxLineDetailCount,
            });

            if (this.getThemeManager().getParam('isDark')) {
                editor.setOptions({
                    theme: 'ace/theme/tomorrow_night',
                });
            }

            if (this.isEditMode()) {
                // noinspection JSCheckFunctionSignatures
                editor.getSession().on('change', () => {
                    this.trigger('change', {ui: true});
                });

                editor.getSession().setUseWrapMode(true);
            }

            if (this.isReadMode()) {
                editor.setReadOnly(true);
                // noinspection JSUnresolvedReference
                editor.renderer.$cursorLayer.element.style.display = "none";
                editor.renderer.setShowGutter(false);
            }

            editor.setShowPrintMargin(false);
            editor.getSession().setUseWorker(false);
            editor.commands.removeCommand('find');
            editor.setHighlightActiveLine(false);

            const JavaScriptMode = ace.require('ace/mode/javascript').Mode;

            editor.session.setMode(new JavaScriptMode());

            if (!this.insertDisabled && !this.isReadMode()) {
                this.initAutocomplete();
            }
        }
    }

    fetch() {
        const data = {};

        let value = this.editor.getValue();

        if (value === '') {
            value = null;
        }

        data[this.name] = value;

        return data;
    }

    addAttribute() {
        this.createView('dialog', 'views/admin/formula/modals/add-attribute', {
            scope: this.targetEntityType,
        }, view => {
            view.render();

            this.listenToOnce(view, 'add', (attribute) => {
                this.editor.insert(attribute);

                this.clearView('dialog');
            });
        });
    }

    addFunction() {
        this.createView('dialog', 'views/admin/formula/modals/add-function', {
            scope: this.targetEntityType,
            functionDataList: this.getFunctionDataList(),
        }, view => {
            view.render();

            this.listenToOnce(view, 'add', (string) => {
                this.editor.insert(string);

                this.clearView('dialog');
            });
        });
    }

    /**
     * @private
     * @return {{
     *     name: string,
     *     insertText?: string,
     *     returnType?: string,
     * }[]}
     */
    getFunctionDataList() {
        let list = [...this.getMetadata().get(['app', 'formula', 'functionList'], [])]
            .filter(item => item.insertText);

        if (this.options.additionalFunctionDataList) {
            list = list.concat(this.options.additionalFunctionDataList);
        }

        const allowedFunctionList = /** @type string[] */this.options.allowedFunctionList;

        if (allowedFunctionList) {
            list = list.filter(/** {name: string} */item => {
                for (const func of allowedFunctionList) {
                    if (func.endsWith('\\') && item.name.startsWith(func)) {
                        return true;
                    }

                    if (item.name === func) {
                        return true;
                    }
                }

                return false;
            });
        }

        if (!this.targetEntityType) {
            list = list.filter(item => {
                if (item.name.indexOf('entity\\') === 0) {
                    return false;
                }

                return true;
            });
        }

        return list;
    }

    initAutocomplete() {
        const functionItemList = this.getFunctionDataList().filter(item => item.insertText);

        const attributeList = this.getFormulaAttributeList();

        ace.require('ace/ext/language_tools');

        this.editor.setOptions({
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true,
        });

        // noinspection JSUnusedGlobalSymbols
        const completer = {
            identifierRegexps: [/[\\a-zA-Z0-9{}\[\].$'"]/],

            getCompletions: function (editor, session, pos, prefix, callback) {
                const matchedFunctionItemList = functionItemList
                    .filter(originalItem => {
                        const text = originalItem.name.toString().toLowerCase();

                        if (text.indexOf(prefix) === 0) {
                            return true;
                        }

                        const parts = text.split('\\');

                        if (parts[parts.length - 1].indexOf(prefix) === 0) {
                            return true;
                        }

                        if (parts.length > 2 && parts[parts.length - 2].indexOf(prefix) === 0) {
                            return true;
                        }

                        return false;
                    });

                let itemList = matchedFunctionItemList.map((item) => {
                    return {
                        caption: item.name + '()',
                        value: item.insertText,
                        meta: item.returnType || null,
                        completer: {
                            insertMatch: (editor, data) => {
                                editor.completer.insertMatch({value: data.value});

                                const index = data.value.indexOf('(');

                                if (!~index) {
                                    return;
                                }

                                if (~data.value.indexOf('()')) {
                                    return;
                                }

                                const pos = editor.selection.getCursor();

                                // noinspection JSCheckFunctionSignatures
                                editor.gotoLine(
                                    pos.row + 1,
                                    pos.column - data.value.length + index + 1
                                );
                            },
                        },
                    };
                });

                const matchedAttributeList = attributeList
                    .filter(item => {
                        if (item.indexOf(prefix) === 0) {
                            return true;
                        }

                        return false;
                    });

                const itemAttributeList = matchedAttributeList.map((item) => {
                    return {
                        name: item,
                        value: item,
                        meta: 'attribute',
                    };
                });

                // noinspection JSCheckFunctionSignatures
                itemList = itemList.concat(itemAttributeList);

                callback(null, itemList);
            }
        };

        this.editor.completers = [completer];
    }

    getFormulaAttributeList() {
        if (!this.targetEntityType) {
            return [];
        }

        const attributeList = this.getFieldManager()
            .getEntityTypeAttributeList(this.targetEntityType)
            .concat(['id'])
            .sort();

        const links = this.getMetadata().get(['entityDefs', this.targetEntityType, 'links']) || {};

        const linkList = [];

        Object.keys(links).forEach((link) => {
            const type = links[link].type;

            if (!type) {
                return;
            }

            if (~['belongsToParent', 'hasOne', 'belongsTo'].indexOf(type)) {
                linkList.push(link);
            }
        });

        linkList.sort();

        linkList.forEach((link) => {
            const scope = links[link].entity;

            if (!scope) {
                return;
            }

            if (links[link].disabled) {
                return;
            }

            const linkAttributeList = this.getFieldManager()
                .getEntityTypeAttributeList(scope)
                .sort();

            linkAttributeList.forEach((item) => {
                attributeList.push(link + '.' + item);
            });
        });

        return attributeList;
    }

    checkSyntax() {
        const expression = this.editor.getValue();

        if (!expression) {
            Espo.Ui.success(
                this.translate('checkSyntaxSuccess', 'messages', 'Formula')
            );

            return;
        }

        Espo.Ajax
            .postRequest('Formula/action/checkSyntax', {expression: expression})
            .then(/** Record */response => {
                if (response.isSuccess) {
                    Espo.Ui.success(
                        this.translate('checkSyntaxSuccess', 'messages', 'Formula')
                    );

                    return;
                }

                let message = this.translate('checkSyntaxError', 'messages', 'Formula');

                if (response.message) {
                    message += ' ' + response.message;
                }

                Espo.Ui.error(message);
            });
    }
}

export default FormulaFieldView;
