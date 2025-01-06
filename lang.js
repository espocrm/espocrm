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

/**
 * Builds language files from a PO file.
 *
 * Command example: `node lang de_DE`.
 *
 * A PO file should be located in `build` directory: `build/espocrm-lang_CODE.po`.
 * Language files will be created in `build` directory.
 *
 * You specify a module with `--module=` parameter. It will build only for the specified module.
 */

const Lang = require('./js/lang');
const path = require('path');
const fs = require('fs');

if (process.argv.length < 3) {
    throw new Error('You need to pass a language code as a second parameter.');
}

let espoPath = path.dirname(fs.realpathSync(__filename)) + '';
let language = process.argv[2];

let poPath = null;
let onlyModuleName = null;

if (process.argv.length > 2) {
    for (let i in process.argv) {
        if (~process.argv[i].indexOf('--module=')) {
            onlyModuleName = process.argv[i].substring(('--module=').length);
        }

        if (~process.argv[i].indexOf('--path=')) {
            poPath = process.argv[i].substring(('--path=').length);
        }
    }
}

if (!poPath) {
    poPath = espoPath + '/build/' + 'espocrm-' + language;

    if (onlyModuleName) {
        poPath += '-' + onlyModuleName;
    }

    poPath += '.po';
}

new Lang(language, poPath, espoPath, onlyModuleName).run();
