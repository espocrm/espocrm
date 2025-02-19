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

import ThemeSettingsFieldView from 'views/settings/fields/theme';

export default class extends ThemeSettingsFieldView {

    setupOptions() {
        this.params.options = Object.keys(this.getMetadata().get('themes') || {})
            .sort((v1, v2) => {
                if (v2 === 'EspoRtl') {
                    return -1;
                }

                return this.translate(v1, 'themes').localeCompare(this.translate(v2, 'themes'));
            });

        this.params.options.unshift('');
    }

    setupTranslation() {
        super.setupTranslation();

        this.translatedOptions = this.translatedOptions || {};

        const defaultTheme = this.getConfig().get('theme');
        const defaultTranslated = this.translatedOptions[defaultTheme] || defaultTheme;

        this.translatedOptions[''] = `${this.translate('Default')} · ${defaultTranslated}`;
    }

    afterRenderDetail() {
        const navbar = this.getNavbarValue() || this.getDefaultNavbar();

        if (navbar) {
            this.$el
                .append(' ')
                .append(
                    $('<span>').addClass('text-muted chevron-right')
                )
                .append(' ')
                .append(
                    $('<span>').text(this.translate(navbar, 'themeNavbars'))
                )
        }
    }
}
