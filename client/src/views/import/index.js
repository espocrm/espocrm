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

/** @module views/import/index */

import View from 'view';

class IndexImportView extends View {

    template = 'import/index'

    formData = null
    fileContents = null

    data() {
        return {
            fromAdmin: this.options.fromAdmin,
        };
    }

    setup() {
        this.entityType = this.options.entityType || null;

        this.startFromStep = 1;

        if (this.options.formData || this.options.fileContents) {
            this.formData = this.options.formData || {};
            this.fileContents = this.options.fileContents || null;

            this.entityType = this.formData.entityType || null;

            if (this.options.step) {
                this.startFromStep = this.options.step;
            }
        }
    }

    changeStep(num, result) {
        this.step = num;

        if (num > 1) {
            this.setConfirmLeaveOut(true);
        }

        this.createView('step', 'views/import/step' + num.toString(), {
            selector: '> .import-container',
            entityType: this.entityType,
            formData: this.formData,
            result: result,
        }, view => {
            view.render();
        });

        let url = '#Import';

        if (this.options.fromAdmin && this.step === 1) {
            url = '#Admin/import';
        }

        if (this.step > 1) {
            url += '/index/step=' + this.step;
        }

        this.getRouter().navigate(url, {trigger: false});
    }

    afterRender() {
        this.changeStep(this.startFromStep);
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate('Import', 'labels', 'Admin'));
    }

    setConfirmLeaveOut(value) {
        this.getRouter().confirmLeaveOut = value;
    }
}

export default IndexImportView;
