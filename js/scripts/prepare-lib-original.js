/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

const fs = require('fs');
const buildUtils = require('../build-utils');

const libs = require('./../../frontend/libs.json');
const bundleConfig = require('../../frontend/bundle-config.json');

const libDir = './client/lib';
const originalLibDir = './client/lib/original';
const libCrmDir = './client/modules/crm/lib';
const originalLibCrmDir = './client/modules/crm/lib/original';

[libDir, originalLibDir, libCrmDir, originalLibCrmDir]
    .filter(path => !fs.existsSync(path))
    .forEach(path => fs.mkdirSync(path));

let bundleFiles = Object.keys(bundleConfig.chunks)
    .map(name => {
        let namePart = 'espo-' + name;

        return namePart + '.js';
    });

fs.readdirSync(originalLibDir)
    .filter(file => !bundleFiles.includes(file))
    .forEach(file => fs.unlinkSync(originalLibDir + '/' + file));

fs.readdirSync(originalLibCrmDir)
    .forEach(file => fs.unlinkSync(originalLibCrmDir + '/' + file));

const stripSourceMappingUrl = path => {
    /** @var {string} */
    let originalContents = fs.readFileSync(path, {encoding: 'utf-8'});

    let re = /^\/\/# sourceMappingURL.*/gm;

    if (!originalContents.match(re)) {
        return;
    }

    let contents = originalContents.replaceAll(re, '');

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

/** @var {string[]} */
const libSrcList = buildUtils.getBundleLibList(libs);

let amdIdMap = {};
let suppressAmdMap = {};

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

libSrcList.forEach(src => {
    let dest = originalLibDir + '/' + src.split('/').slice(-1);

    fs.copyFileSync(src, dest);
    stripSourceMappingUrl(dest);

    if (suppressAmdMap[src]) {
        addSuppressAmd(dest);
    }

    let key = amdIdMap[src];

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

        let key = amdIdMap[item.src];

        if (key) {
            addLoadingSubject(item.originalDest, key);
        }
    });
