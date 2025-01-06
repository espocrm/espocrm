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
* Buils a PO file for a specific language or all languages.
* The built PO file will be available in `build` directory.
*
* Command example: `node po en_US`.
*
* Options:
* * --module={ModuleName} - only specific module;
* * --all - all languages.
*/

const PO = require('./js/po');
const path = require('path');
const fs = require('fs');

let language = process.argv[2] || null;

let onlyModuleName = null;

if (process.argv.length > 2) {
    for (let i in process.argv) {
        if (~process.argv[i].indexOf('--module=')) {
            onlyModuleName = process.argv[i].substr(('--module=').length);
        }

        if (~process.argv[i].indexOf('--all')) {
            language = '--all';
        }
    }
}

let espoPath = path.dirname(fs.realpathSync(__filename)) + '';

let po = new PO(espoPath, language, onlyModuleName);

language === '--all' ?
    po.runAll() :
    po.run();

