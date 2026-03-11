/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import {inject} from 'di';
import ThemeManager from 'theme-manager';

export default class WindowPanelHelper {

    /**
     * @type {import('view').default}
     * @private
     */
    view

    /**
     * @private
     * @type {boolean}
     */
    overflowWasHidden = false

    /**
     * @private
     */
    onResizeBind

    /**
     * @private
     * @type {ThemeManager}
     */
    @inject(ThemeManager)
    themeManager

    /**
     * @param {import('view').default} view
     */
    constructor(view) {
        this.view = view;

        view.listenToOnce(view, 'remove', () => {
            window.removeEventListener('resize', this.onResizeBind)

            if (this.overflowWasHidden) {
                document.body.style.overflow = 'unset';

                this.overflowWasHidden = false;
            }
        });

        this.onResizeBind = this.onResize.bind(this);

        window.addEventListener('resize', this.onResizeBind);

        this.navbarPanelHeightSpace = this.themeManager.getParam('navbarPanelHeightSpace') ?? 100;
        this.navbarPanelBodyMaxHeight = this.themeManager.getParam('navbarPanelBodyMaxHeight') ?? 600;
        this.xsWidth = this.themeManager.getParam('screenWidthXs');

        this.onResize();
    }

    /**
     * @private
     */
    onResize() {
        const windowHeight = window.innerHeight;
        const windowWidth = window.innerWidth;

        const panelBody = this.view.element?.querySelector('.panel-body');
        const heading = this.view.element?.querySelector('.panel-heading');

        if (!(panelBody instanceof HTMLElement)) {
            return;
        }

        const diffHeight = heading?.outerHeight ?? 0;

        const cssParams = {};

        if (windowWidth <= this.xsWidth) {
            cssParams.height = (windowHeight - diffHeight) + 'px';
            cssParams.overflow = 'auto';

            document.body.style.overflow = 'hidden';

            this.overflowWasHidden = true;
        } else {
            cssParams.height = 'unset';
            cssParams.overflow = 'none';

            if (this.overflowWasHidden) {
                panelBody.style.overflow = 'unset';

                this.overflowWasHidden = false;
            }

            if (windowHeight - this.navbarPanelBodyMaxHeight < this.navbarPanelHeightSpace) {
                const maxHeight = windowHeight - this.navbarPanelHeightSpace;

                cssParams.maxHeight = maxHeight + 'px';
            }
        }

        for (const [param, value] of Object.entries(cssParams)) {
            panelBody.style[param] = value;
        }
    }
}
