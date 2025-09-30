/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

import RecordController from 'controllers/record';

class ImportController extends RecordController {

    defaultAction = 'index'

    storedData

    checkAccessGlobal() {
        if (this.getAcl().checkScope('Import')) {
            return true;
        }

        return false;
    }

    checkAccess(action) {
        if (this.getAcl().checkScope('Import')) {
            return true;
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @param {{
     *     step?: int|string,
     *     fromAdmin?: boolean,
     *     formData?: Object
     * }} o
     */
    actionIndex(o) {
        o = o || {};

        let step = null;

        if (o.step) {
            step = parseInt(step);
        }

        let formData = null;
        let fileContents = null;

        if (o.formData) {
            this.storedData = undefined;
        }

        if (this.storedData) {
            formData = this.storedData.formData;
            fileContents = this.storedData.fileContents;
        }

        if (!formData) {
            step = null;
        }

        formData = formData || o.formData;

        this.main('views/import/index', {
            step: step,
            formData: formData,
            fileContents: fileContents,
            fromAdmin: o.fromAdmin,
        }, /** module:views/import/index */ view => {
            this.listenTo(view, 'change', () => {
                this.storedData = {
                    formData: view.formData,
                    fileContents: view.fileContents,
                };
            });

            this.listenTo(view, 'done', () => {
                this.storedData = undefined;
            });

            view.render();
        });
    }
}

export default ImportController;
