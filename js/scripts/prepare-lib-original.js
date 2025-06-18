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

const fs = require('fs');
const cp = require('child_process');
const buildUtils = require('../build-utils');

// @todo Introduce libs-provider.
/**
 * @type {{
 *     src?: string,
 *     dest?: string,
 *     bundle?: boolean,
 *     amdId?: string,
 *     suppressAmd?: boolean,
 *     minify?: boolean,
 *     prepareCommand?: string,
 *     name?: string,
 *     files?: {
 *         src: string,
 *         dest: string,
 *     }[],
 * }[]}
 */
const libs = require('./../../frontend/libs.json');

const bundleConfig = require('../../frontend/bundle-config.json');

const libDir = './client/lib';
const originalLibDir = './client/lib/original';
const libCrmDir = './client/modules/crm/lib';
const originalLibCrmDir = './client/modules/crm/lib/original';

[libDir, originalLibDir, libCrmDir, originalLibCrmDir]
    .filter(path => !fs.existsSync(path))
    .forEach(path => fs.mkdirSync(path));

const bundleFiles = Object.keys(bundleConfig.chunks)
    .map(name => {
        const namePart = 'espo-' + name;

        return namePart + '.js';
    });

fs.readdirSync(originalLibDir)
    .filter(file => !bundleFiles.includes(file))
    .forEach(file => fs.unlinkSync(originalLibDir + '/' + file));

fs.readdirSync(originalLibCrmDir)
    .forEach(file => fs.unlinkSync(originalLibCrmDir + '/' + file));

libs.filter(it => it.prepareCommand)
    .forEach(it => {
        cp.execSync(it.prepareCommand, {stdio: ['ignore', 'ignore', 'pipe']});
    });

const stripSourceMappingUrl = path => {
    /** @var {string} */
    const originalContents = fs.readFileSync(path, {encoding: 'utf-8'});

    const re = /^\/\/# sourceMappingURL.*/gm;

    if (!originalContents.match(re)) {
        return;
    }

    const contents = originalContents.replaceAll(re, '');

    fs.writeFileSync(path, contents, {encoding: 'utf-8'});
}

const addLoadingSubject = (path, subject) => {
    /** @var {string} */
    let contents = fs.readFileSync(path, {encoding: 'utf-8'});

    contents =
        `Espo.loader.setContextId('${subject}');\n` +
        contents + '\n' +
        `Espo.loader.setContextId(null);\n`;

    fs.writeFileSync(path, contents, {encoding: 'utf-8'});
}

const addSuppressAmd = path => {
    /** @var {string} */
    let contents = fs.readFileSync(path, {encoding: 'utf-8'});

    contents =
        `var _previousDefineAmd = define.amd; define.amd = false;\n` +
        contents + '\n' +
        `define.amd = _previousDefineAmd;\n`;

    fs.writeFileSync(path, contents, {encoding: 'utf-8'});
}

const amdIdMap = {};
const suppressAmdMap = {};

libs.forEach(item => {
    if (!item.amdId || !item.bundle || item.files) {
        return;
    }

    if (item.suppressAmd) {
        suppressAmdMap[item.src] = true;

        return;
    }

    amdIdMap[item.src] = 'lib!' + item.amdId;
});

buildUtils.getBundleLibList(libs, true).forEach(item => {
    const src = item.src;

    const dest = originalLibDir + '/' + item.file;

    fs.copyFileSync(src, dest);
    stripSourceMappingUrl(dest);

    if (suppressAmdMap[src]) {
        addSuppressAmd(dest);
    }

    const key = amdIdMap[src];

    if (key) {
        addLoadingSubject(dest, key);
    }
});

buildUtils.getCopyLibDataList(libs)
    .filter(item => item.minify)
    .forEach(item => {
        fs.copyFileSync(item.src, item.originalDest);
        stripSourceMappingUrl(item.originalDest);

        if (suppressAmdMap[item.src]) {
            addSuppressAmd(item.originalDest);
        }

        const key = amdIdMap[item.src];

        if (key) {
            addLoadingSubject(item.originalDest, key);
        }
    });
