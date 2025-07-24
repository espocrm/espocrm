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

/** @module views/site/master */

import View from 'view';
import $ from 'jquery';
import CollapsedModalBarView from 'views/collapsed-modal-bar';
import {inject} from 'di';
import ShortcutManager from 'helpers/site/shortcut-manager';

class MasterSiteView extends View {

    template = 'site/master'

    views = {
        header: {
            id: 'header',
            view: 'views/site/header',
        },
        main: {
            id: 'main',
            view: false,
        },
        footer: {
            fullSelector: 'body > footer',
            view: 'views/site/footer',
        },
    }

    /**
     * @type {string}
     */
    currentViewKey

    /**
     * @type {string}
     */
    currentName

    /**
     * @internal
     * @type {CollapsedModalBarView}
     */
    collapsedModalBarView


    /**
     * Injected to be loaded early.
     *
     * @private
     * @type {ShortcutManager}
     */
    @inject(ShortcutManager)
    shortcutManager

    showLoadingNotification() {
        Espo.Ui.notifyWait();
    }

    hideLoadingNotification() {
        Espo.Ui.notify(false);
    }

    setup() {
        $(window).on('resize.' + this.cid, () => {
            this.adjustContent();
        });

        this.collapsedModalBarView = new CollapsedModalBarView();

        this.assignView('collapsedModalBar', this.collapsedModalBarView, '> .collapsed-modal-bar');
    }

    /**
     * @return {Bull.View|null}
     */
    getMainView() {
        return this.getView('main');
    }

    onRemove() {
        $(window).off('resize.' + this.cid);
    }

    afterRender() {
        const params = this.getThemeManager().getParam('params');

        const $body = $('body');

        for (const param in params) {
            const value = this.getThemeManager().getParam(param);

            $body.attr('data-' + Espo.Utils.camelCaseToHyphen(param), value);
        }

        const footerView = this.getView('footer');

        if (footerView) {
            const html = footerView.$el.html() || '';

            if ((html.match(/espocrm/gi) || []).length < 2) {
                const text = 'PHAgY2xhc3M9ImNyZWRpdCBzbWFsbCI+JmNvcHk7IDxhIGhyZWY9Imh0dHA6Ly93d3cuZXNwb2Nyb' +
                    'S5jb20iPkVzcG9DUk08L2E+PC9wPg==';

                let decText;

                if (typeof window.atob === "function") {
                    decText = window.atob(text);
                } else if (typeof atob === "function") {
                    decText = atob(text);
                }

                if (decText) {
                    footerView.$el.html(decText);
                }
            }
        }

        this.$content = this.$el.find('> #content');

        this.adjustContent();

        const extensions = this.getHelper().getAppParam('extensions') || [];

        if (this.getConfig().get('maintenanceMode')) {
            this.createView('dialog', 'views/modal', {
                templateContent: '<div class="text-danger">{{complexText viewObject.options.message}}</div>',
                headerText: this.translate('maintenanceMode', 'fields', 'Settings'),
                backdrop: true,
                message: this.translate('maintenanceMode', 'messages'),
                buttonList: [
                    {
                        name: 'close',
                        label: this.translate('Close'),
                    }
                ],
            }, view => {
                view.render();
            });
        }
        else if (this.getHelper().getAppParam('auth2FARequired')) {
            this.createView('dialog', 'views/modals/auth2fa-required', {}, (view) => {
                view.render();
            });
        }
        else if (extensions.length !== 0) {
            this.processExtensions(extensions);
        }
    }

    adjustContent() {
        if (!this.isRendered()) {
            return;
        }

        if (window.innerWidth < this.getThemeManager().getParam('screenWidthXs')) {
            this.isSmallScreen = true;

            let height = window.innerHeight - this.$content.get(0).getBoundingClientRect().top;

            const $navbarCollapse = $('#navbar .navbar-body');

            if ($navbarCollapse.hasClass('in') || $navbarCollapse.hasClass('collapsing')) {
                height += $navbarCollapse.height();
            }

            const footerHeight = $('#footer').height() || 26;

            height -= footerHeight;

            if (height <= 0) {
                this.$content.css('minHeight', '');

                return;
            }

            this.$content.css('minHeight', height + 'px');

            return;
        }

        if (this.isSmallScreen) {
            this.$content.css('minHeight', '');
        }

        this.isSmallScreen = false;
    }

    /**
     * @param {{
     *     name: string,
     *     licenseStatus: string,
     *     licenseStatusMessage:? string,
     *     notify: boolean,
     * }[]} list
     */
    processExtensions(list) {
        const messageList = [];

        list.forEach(item => {
            if (!item.notify) {
                return;
            }

            const message = item.licenseStatusMessage ??
                'extensionLicense' +
                Espo.Utils.upperCaseFirst(
                    Espo.Utils.hyphenToCamelCase(item.licenseStatus.toLowerCase())
                );

            messageList.push(
                this.translate(message, 'messages')
                    .replace('{name}', item.name)
            );
        });

        if (!messageList.length) {
            return;
        }

        let message = messageList.join('\n\n');

        message = this.getHelper().transformMarkdownText(message);

        const dialog = new Espo.Ui.Dialog({
            backdrop: 'static',
            buttonList: [
                {
                    name: 'close',
                    text: this.translate('Close'),
                    className: 'btn-s-wide',
                    onClick: () => dialog.close(),
                }
            ],
            className: 'dialog-confirm text-danger',
            body: message.toString(),
        });

        dialog.show();
    }
}

export default MasterSiteView;
