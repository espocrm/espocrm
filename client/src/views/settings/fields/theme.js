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

import EnumFieldView from 'views/fields/enum';
import ThemeManager from 'theme-manager';
import Select from 'ui/select';

export default class ThemeSettingsFieldView extends EnumFieldView {

    // language=Handlebars
    editTemplateContent = `
        <div class="grid-auto-fit-xxs">
            <div>
                <select data-name="{{name}}" class="form-control main-element">
                    {{options
                        params.options value
                        scope=scope
                        field=name
                        translatedOptions=translatedOptions
                        includeMissingOption=true
                        styleMap=params.style
                    }}
                </select>
            </div>
            {{#if navbarOptionList.length}}
            <div>
                <select data-name="themeNavbar" class="form-control">
                    {{options navbarOptionList navbar translatedOptions=navbarTranslatedOptions}}
                </select>
            </div>
            {{/if}}
        </div>
    `

    data() {
        const data = super.data();

        data.navbarOptionList = this.getNavbarOptionList();
        data.navbar = this.getNavbarValue() || this.getDefaultNavbar();

        data.navbarTranslatedOptions = {};
        data.navbarOptionList.forEach(item => {
            data.navbarTranslatedOptions[item] = this.translate(item, 'themeNavbars');
        });

        return data;
    }

    setup () {
        super.setup();

        this.initThemeManager();

        this.model.on('change:theme', (m, v, o) => {
            this.initThemeManager()

            if (o.ui) {
                this.reRender()
                    .then(() => Select.focus(this.$element, {noTrigger: true}));
            }
        })
    }

    afterRenderEdit() {
        this.$navbar = this.$el.find('[data-name="themeNavbar"]');

        this.$navbar.on('change', () => this.trigger('change'));

        Select.init(this.$navbar);
    }

    /**
     * @protected
     * @return {string}
     */
    getNavbarValue() {
        const params = this.model.get('themeParams') || {};

        return params.navbar;
    }

    /**
     * @protected
     * @return {Record|null}
     */
    getNavbarDefs() {
        if (!this.themeManager) {
            return null;
        }

        const params = this.themeManager.getParam('params');

        if (!params || !params.navbar) {
            return null;
        }

        return Espo.Utils.cloneDeep(params.navbar);
    }

    /**
     * @private
     * @return {string[]}
     */
    getNavbarOptionList() {
        const defs = this.getNavbarDefs();

        if (!defs) {
            return [];
        }

        const optionList = defs.options || [];

        if (!optionList.length || optionList.length === 1) {
            return [];
        }

        return optionList;
    }

    /**
     * @protected
     * @return {string|null}
     */
    getDefaultNavbar() {
        const defs = this.getNavbarDefs() || {};

        return defs.default || null;
    }

    /**
     * @private
     */
    initThemeManager() {
        const theme = this.model.get('theme');

        if (!theme) {
            this.themeManager = null;

            return;
        }

        this.themeManager = new ThemeManager(
            this.getConfig(),
            this.getPreferences(),
            this.getMetadata(),
            theme
        );
    }

    getAttributeList() {
        return [this.name, 'themeParams'];
    }

    setupOptions() {
        this.params.options = Object.keys(this.getMetadata().get('themes') || {})
            .sort((v1, v2) => {
                if (v2 === 'EspoRtl') {
                    return -1;
                }

                return this.translate(v1, 'theme')
                    .localeCompare(this.translate(v2, 'theme'));
            });
    }

    fetch() {
        const data = super.fetch();

        const params = {};

        if (this.$navbar.length) {
            params.navbar = this.$navbar.val();
        }

        data.themeParams = params;

        return data;
    }
}
