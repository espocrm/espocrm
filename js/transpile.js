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

const {Transpiler} = require('espo-frontend-build-tools');

let file;

let fIndex = process.argv.findIndex(item => item === '-f');

if (fIndex > 0) {
    file = process.argv.at(fIndex + 1);

    if (!file) {
        throw new Error(`No file specified.`);
    }
}

const transpiler1 = new Transpiler({
    file: file,
});

const transpiler2 = new Transpiler({
    mod: 'crm',
    path: 'client/modules/crm',
    file: file,
});

const result1 = transpiler1.process();
const result2 = transpiler2.process();

let count = result1.transpiled.length + result2.transpiled.length;
let copiedCount = result1.copied.length + result2.copied.length;

console.log(`\n  transpiled: ${count}, copied: ${copiedCount}`)
