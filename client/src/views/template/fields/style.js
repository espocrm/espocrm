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

class TemplateStyleFieldView extends TextFieldView {

    // language=Handlebars
    detailTemplateContent = `
        {{#if isNotEmpty}}
            <div id="{{containerId}}">{{value}}</div>
        {{else}}
            <span class="none-value">{{translate 'None'}}</span>
        {{/if}}
    `

    // language=Handlebars
    editTemplateContent = `
        <div id="{{containerId}}">{{value}}</div>
    `

    height = 46
    maxLineDetailCount = 80
    maxLineEditCount = 200

    setup() {
        super.setup();

        this.height = this.options.height || this.params.height || this.height;

        this.maxLineDetailCount =
            this.options.maxLineDetailCount ||
            this.params.maxLineDetailCount ||
            this.maxLineDetailCount;

        this.maxLineEditCount =
            this.options.maxLineEditCount ||
            this.params.maxLineEditCount ||
            this.maxLineEditCount;

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
                ace = lib;

                const list = [
                    Espo.loader.requirePromise('lib!ace-ext-language_tools'),
                    Espo.loader.requirePromise('lib!ace-mode-css'),
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
            this.$editor.css('fontSize', 'var(--font-size-base)');

            if (this.mode === this.MODE_EDIT) {
                this.$editor.css('minHeight', this.height + 'px');
            }

            const editor = this.editor = ace.edit(this.containerId);

            editor.setOptions({fontFamily: 'var(--font-family-monospace)'});
            editor.setFontSize('var(--font-size-base)');
            editor.container.style.lineHeight = 'var(--line-height-computed)';
            editor.renderer.updateFontSize();

            editor.setOptions({
                maxLines: this.mode === this.MODE_EDIT ? this.maxLineEditCount : this.maxLineDetailCount,
                enableLiveAutocompletion: true,
            });

            if (this.getThemeManager().getParam('isDark')) {
                editor.setOptions({
                    theme: 'ace/theme/tomorrow_night',
                });
            }

            if (this.isEditMode()) {
                editor.getSession().on('change', () => {
                    this.trigger('change', {ui: true});
                });

                editor.getSession().setUseWrapMode(true);
            }

            if (this.isReadMode()) {
                editor.setReadOnly(true);
                editor.renderer.$cursorLayer.element.style.display = 'none';
                editor.renderer.setShowGutter(false);
            }

            editor.setShowPrintMargin(false);
            editor.getSession().setUseWorker(false);
            editor.commands.removeCommand('find');
            editor.setHighlightActiveLine(false);

            const Mode = ace.require('ace/mode/css').Mode;

            editor.session.setMode(new Mode());
        }
    }

    fetch() {
        const data = {};

        data[this.name] = this.editor.getValue();

        return data;
    }
}

export default TemplateStyleFieldView;
