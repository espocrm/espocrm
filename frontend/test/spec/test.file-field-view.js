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

describe('FileFieldView', () => {
    let FileFieldView;

    beforeAll(done => {
        // Stubs for views/fields/link and helpers/* are pre-registered in
        // frontend/test/stubs/file-field-view-deps.js (a srcFile that loads
        // before views/fields/file.js so the AMD factory can resolve them).
        require(['views/fields/file'], FileFieldViewClass => {
            // EspoCRM AMD loader unwraps the default export; value is the class.
            FileFieldView = FileFieldViewClass;
            done();
        });
    });

    /**
     * Builds a minimal FileFieldView-like object without triggering the full
     * view constructor. Only the properties that `data()` reads are set.
     *
     * @param {null|'user'|'environment'|undefined} captureValue
     * @returns {FileFieldView}
     */
    function createInstance(captureValue) {
        const instance = Object.create(FileFieldView.prototype);
        instance.params = {capture: captureValue};
        instance.model = {
            get: () => null,
            has: () => false,
        };
        instance.idName = 'fileId';
        instance.acceptAttribute = null;
        instance.mode = 'detail';
        instance.MODE_EDIT = 'edit';
        return instance;
    }

    describe('#data capture param', () => {
        it('passes capture: "user" through to template data', () => {
            const view = createInstance('user');
            expect(view.data().capture).toBe('user');
        });

        it('passes capture: "environment" through to template data', () => {
            const view = createInstance('environment');
            expect(view.data().capture).toBe('environment');
        });

        it('passes capture: null through to template data', () => {
            const view = createInstance(null);
            expect(view.data().capture).toBeNull();
        });

        it('passes capture: undefined when not set in params', () => {
            const view = createInstance(undefined);
            expect(view.data().capture).toBeUndefined();
        });
    });
});
