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
const archiver = require('archiver');
const process = require('process');
const buildUtils = require('./build-utils');

const bundleConfig = require('../frontend/bundle-config.json');

const exec = cp.exec;

/**
 * Builds upgrade packages.
 */
class Diff
{
    constructor(espoPath, params) {
        this.espoPath = espoPath;
        this.params = params || {};
    }

    _getTagList() {
        const dirInitial = process.cwd();

        process.chdir(this.espoPath);

        const tagsString = cp.execSync('git tag -l --sort=-v:refname').toString();
        const tagList = tagsString.trim().split("\n");

        process.chdir(dirInitial);

        return tagList;
    }

    buildClosestUpgradePackages() {
        const versionFromList = this._getPreviousVersionList(true);

        this.buildMultipleUpgradePackages(versionFromList);
    }

    buildAllUpgradePackages() {
        const versionFromList = this._getPreviousVersionList();

        this.buildMultipleUpgradePackages(versionFromList);
    }

    _getPreviousVersionList(closest) {
        const dirInitial = process.cwd();

        const version = (require(this.espoPath + '/package.json') || {}).version;

        process.chdir(this.espoPath);

        const tagList = this._getTagList();

        const versionFromList = [];

        const minorVersionNumber = version.split('.')[1];
        const hotfixVersionNumber = version.split('.')[2];
        const majorVersionNumber = version.split('.')[0];

        for (let i = 0; i < tagList.length; i++) {
            const tag = tagList[i];

            if (tag === '') {
                continue;
            }

            if (tag === version) {
                continue;
            }

            if (!~tag.indexOf('beta') && !~tag.indexOf('alpha')) {
                const minorVersionNumberI = tag.split('.')[1];

                if (minorVersionNumberI !== minorVersionNumber) {
                    versionFromList.push(tag);

                    break;
                }
            }
        }

        if (hotfixVersionNumber !== '0') {
            for (let i = 0; i < tagList.length; i++) {
                const tag = tagList[i];

                const patchVersionNumberI = tag.split('.')[2];
                const minorVersionNumberI = tag.split('.')[1];
                const majorVersionNumberI = tag.split('.')[0];

                if (
                    closest &&
                    (
                        minorVersionNumberI !== minorVersionNumber ||
                        majorVersionNumberI !== majorVersionNumber ||
                        parseInt(patchVersionNumberI) !== parseInt(hotfixVersionNumber) - 1
                    )
                ) {
                    continue;
                }

                if (tag === version) {
                    continue;
                }

                if (!~tag.indexOf('beta') && !~tag.indexOf('alpha')) {
                    versionFromList.push(tag);

                    if (patchVersionNumberI === '0') {
                        break;
                    }
                }
            }
        }

        process.chdir(dirInitial);

        return versionFromList;
    }

    buildMultipleUpgradePackages(versionFromList) {
        const params = this.params;
        const espoPath = this.espoPath;

        async function buildMultiple() {
            for (const versionFrom of versionFromList) {
                const diff = new Diff(espoPath, params);

                await diff.buildUpgradePackage(versionFrom);
            }
        }

        buildMultiple();
    }

    _versionExists(version) {
        return ~this._getTagList().indexOf(version);
    }

    buildUpgradePackage(versionFrom) {
        const params = this.params;
        const espoPath = this.espoPath;

        if (!this._versionExists(versionFrom)) {
            throw new Error('Version ' + versionFrom + ' does not exist.');
        }

        return new Promise(resolve => {
            const acceptedVersionName = params.acceptedVersionName || versionFrom;
            const isDev = params.isDev;
            const withVendor = params.withVendor ?? true;
            const forceScripts = params.forceScripts;

            const version = (require(espoPath + '/package.json') || {}).version;
            const composerData = require(espoPath + '/composer.json') || {};

            const currentPath = espoPath;
            const buildRelPath = 'build/EspoCRM-' + version;
            const buildPath = currentPath + '/' + buildRelPath;
            const diffFilePath = currentPath + '/build/diff';
            const diffBeforeUpgradeFolderPath = currentPath + '/build/diffBeforeUpgrade';

            const tempFolderPath = currentPath + '/build/upgradeTmp';
            const folderName = 'EspoCRM-upgrade-' + acceptedVersionName + '-to-' + version;
            const upgradePath = currentPath + '/build/' + folderName;
            const zipPath = currentPath + '/build/' + folderName + '.zip';
            let upgradeDataFolder = versionFrom + '-' + version;


            const isMinorVersion =
                versionFrom.split('.')[1] !== version.split('.')[1] ||
                versionFrom.split('.')[0] !== version.split('.')[0];

            if (isMinorVersion) {
                upgradeDataFolder = version.split('.')[0] + '.' + version.split('.')[1];
            }

            const upgradeDataFolderPath = currentPath + '/upgrades/' + upgradeDataFolder;
            const upgradeFolderExists = fs.existsSync(upgradeDataFolderPath);

            let upgradeData = {};

            if (upgradeFolderExists) {
                upgradeData = require(upgradeDataFolderPath + '/data.json') || {};
            }

            const beforeUpgradeFileList = upgradeData.beforeUpgradeFiles || [];

            deleteDirRecursively(diffFilePath);
            deleteDirRecursively(diffBeforeUpgradeFolderPath);
            deleteDirRecursively(upgradePath);
            deleteDirRecursively(tempFolderPath);

            if (fs.existsSync(zipPath)) {
                fs.unlinkSync(zipPath);
            }

            process.chdir(espoPath);

            this._notifyIfBadBranch();

            if (!fs.existsSync(buildPath)) {
                throw new Error(
                    "EspoCRM is not built. You need to run 'grunt' before building an upgrade package."
                );
            }

            if (!fs.existsSync(upgradePath)) {
                fs.mkdirSync(upgradePath);
            }

            if (!fs.existsSync(upgradePath + '/files')) {
                fs.mkdirSync(upgradePath + '/files');
            }

            if (fs.existsSync(upgradeDataFolderPath + '/beforeUpgradeFiles')) {
                cp.execSync(
                    'cp -r ' + upgradeDataFolderPath + '/beforeUpgradeFiles ' + upgradePath + '/beforeUpgradeFiles'
                );
            }

            if (beforeUpgradeFileList.length) {
                if (!fs.existsSync(upgradePath + '/beforeUpgradeFiles')) {
                    fs.mkdirSync(upgradePath + '/beforeUpgradeFiles');
                }
            }

            const libData = this._getLibData({
                versionFrom: versionFrom,
                currentPath: currentPath,
            });

            const deleteFileList = this._getDeletedFileList(versionFrom)
                .concat(libData.filesToDelete)
                .filter((item, i, list) => list.indexOf(item) === i);

            const tagList = this._getTagList();

            process.chdir(buildPath);

            let fileList = upgradeData.mandatoryFiles || [];

            const stdout = cp.execSync('git diff --name-only ' + versionFrom).toString();

            (stdout || '').trim().split('\n').forEach(file => {
                if (file === '') {
                    return;
                }

                fileList.push(file);
            });

            libData.filesToCopy.forEach(item => fileList.push(item));

            fileList.push('application/Espo/Resources/defaults.php');

            fileList.push('client/lib/espo.js');
            fileList.push('client/lib/espo.js.map');
            fileList.push('client/lib/templates.tpl');
            fileList.push('client/lib/original/espo.js');

            Object.keys(bundleConfig.chunks)
                .map(name => {
                    const namePart = `espo-${name}`;

                    fileList.push(`client/lib/${namePart}.js`);
                    fileList.push(`client/lib/${namePart}.js.map`);
                    fileList.push(`client/lib/original/${namePart}.js`);
                });

            fs.readdirSync('client/css/espo/').forEach(file => {
                fileList.push('client/css/espo/' + file);
            });

            fileList = fileList.filter(item => fs.existsSync(item));

            fs.writeFileSync(diffFilePath, fileList.join('\n'));

            if (beforeUpgradeFileList.length) {
                fs.writeFileSync(diffBeforeUpgradeFolderPath, beforeUpgradeFileList.join('\n'));
            }

            if (beforeUpgradeFileList.length) {
                cp.execSync(
                    'xargs -a ' + diffBeforeUpgradeFolderPath +
                    ' cp -p --parents -t ' + upgradePath + '/beforeUpgradeFiles'
                );
            }

            if (!isDev || forceScripts) {
                if (fs.existsSync(upgradeDataFolderPath + '/scripts')) {
                    cp.execSync('cp -r ' + upgradeDataFolderPath + '/scripts ' + upgradePath + '/scripts');
                }
            }

            execute('xargs -a ' + diffFilePath + ' cp -p --parents -t ' + upgradePath + '/files' , () => {
                const date = this._getCurrentDate();

                let versionList = [];

                tagList.forEach(tag => {
                    if (tag === versionFrom) {
                        versionList.push(tag);
                    }

                    /*if (!tag || tag === version) {
                        return;
                    }*/
                });

                if (isDev) {
                    versionList = [];
                }

                const upgradeName = acceptedVersionName + " to " + version;

                const manifestData = {
                    "name": "EspoCRM Upgrade " + upgradeName,
                    "type": "upgrade",
                    "version": version,
                    "acceptableVersions": versionList,
                    "php": [composerData.require.php],
                    "releaseDate": date,
                    "author": "EspoCRM",
                    "description": "",
                    "delete": deleteFileList,
                };

                const additionalManifestData = upgradeData.manifest || {};

                for (const item in additionalManifestData) {
                    if (Array.isArray(manifestData[item])) {
                        manifestData[item] = manifestData[item].concat(additionalManifestData[item]);

                        continue;
                    }

                    manifestData[item] = additionalManifestData[item];
                }

                fs.writeFileSync(upgradePath + '/manifest.json', JSON.stringify(manifestData, null, '  '));

                if (fs.existsSync(diffFilePath)) {
                    fs.unlinkSync(diffFilePath);
                }

                if (fs.existsSync(diffBeforeUpgradeFolderPath)) {
                    fs.unlinkSync(diffBeforeUpgradeFolderPath);
                }

                if (withVendor) {
                    this._processVendor({
                        versionFrom: versionFrom,
                        currentPath: currentPath,
                        tempFolderPath: tempFolderPath,
                        upgradePath: upgradePath,
                    });
                }

                this._processArchive({
                    upgradeName: upgradeName,
                    zipPath: zipPath,
                    upgradePath: upgradePath,
                })
                .then(() => resolve());
            });
        });
    }

    _notifyIfBadBranch() {
        const currentBranch = cp.execSync('git rev-parse --abbrev-ref HEAD').toString().trim();

        if (
            currentBranch !== 'master' &&
            currentBranch !== 'stable' &&
            currentBranch !== 'fix'
        ) {
            console.log('\x1b[33m%s\x1b[0m', "Warning! You are on " + currentBranch + " branch.");
        }
    }

    _getCurrentDate() {
        const d = new Date();

        let monthN = ((d.getMonth() + 1).toString());
        monthN = monthN.length === 1 ? '0' + monthN : monthN;

        let dateN = d.getDate().toString();
        dateN = dateN.length === 1 ? '0' + dateN : dateN;

        return d.getFullYear().toString() + '-' + monthN + '-' + dateN.toString();
    }

    _getDeletedFileList(versionFrom) {
        const dirInitial = process.cwd();

        process.chdir(this.espoPath);

        let deletedFileList = this._getRepositoryDeletedFileList(versionFrom);
        const previousAllFileList = this._getPreviousAllFileList(versionFrom);
        const actualAllFileList = this._getActualAllFileList();

        previousAllFileList.forEach(file => {
            if (
                !~actualAllFileList.indexOf(file) &&
                !~deletedFileList.indexOf(file)
            ) {
                deletedFileList.push(file);
            }
        });

        deletedFileList = deletedFileList.filter(item => {
            if (
                item.indexOf('tests/') === 0 ||
                item.indexOf('upgrades/') === 0 ||
                item.indexOf('frontend/') === 0||
                item.indexOf('js/') === 0
            ) {
                return false;
            }

            return true;
        });

        process.chdir(dirInitial);

        return deletedFileList;
    }

    _getRepositoryDeletedFileList(versionFrom) {
        const deletedFileList = [];

        const stdout = cp.execSync('git diff --name-only --diff-filter=D ' + versionFrom).toString();

        (stdout || '').trim().split('\n').forEach(file => {
            if (file === '') {
                return;
            }

            deletedFileList.push(file);
        });

        return deletedFileList;
    }

    _getActualAllFileList() {
        const actualAllFileList = [];

        const stdout = cp.execSync('git ls-tree -r --name-only HEAD').toString();

        (stdout || '').trim().split('\n').forEach(file => {
            if (file === '') {
                return;
            }

            actualAllFileList.push(file);
        });

        return actualAllFileList;
    }

    _getPreviousAllFileList(versionFrom) {
        const previousAllFileList = [];

        const stdout = cp.execSync('git ls-tree -r --name-only ' + versionFrom).toString();

        (stdout || '').trim().split('\n').forEach(file => {
            if (file === '') {
                return;
            }

            previousAllFileList.push(file);
        });

        return previousAllFileList;
    }

    _deleteGitFolderInVendor(dir) {
        const folderList = fs.readdirSync(dir, {withFileTypes: true})
            .filter(dirent => dirent.isDirectory())
            .map(dirent => dirent.name);

        folderList.forEach(folder => {
            const path = dir + '/' + folder;

            const gitPath = path + '/.git';

            if (fs.existsSync(gitPath)) {
                deleteDirRecursively(gitPath);
            }
        });
    }

    _getLibData(dto) {
        const data = {
            filesToDelete: [],
            filesToCopy: [],
        };

        const versionFrom = dto.versionFrom;
        const currentPath = dto.currentPath;

        const output = cp.execSync("git show " + versionFrom + " --format=%H").toString();
        const commitHash = output.trim().split("\n")[3];

        if (!commitHash) {
            throw new Error("Couldn't find commit hash.");
        }

        const packageLockOldContents = cp.execSync("git show " + commitHash + ":package-lock.json").toString();
        const packageLockNewContents = cp.execSync("cat " + currentPath + "/package-lock.json").toString();

        const depsOld = JSON.parse(packageLockOldContents).dependencies || {};
        const depsNew = JSON.parse(packageLockNewContents).dependencies || {};

        if (packageLockOldContents === packageLockNewContents) {
            return data;
        }

        let libOldDataList = [];
        let bundledOldDataList = [];

        if (~this._getVersionAllFileList(versionFrom).indexOf('frontend/libs.json')) {
            libOldDataList = JSON
                .parse(
                    cp.execSync("git show " + commitHash + ":frontend/libs.json").toString() || '[]'
                )
                .filter(item => !item.bundle);

            bundledOldDataList = JSON
                .parse(
                    cp.execSync("git show " + commitHash + ":frontend/libs.json").toString() || '[]'
                )
                .filter(item => item.bundle)
                .filter(item => item.src || item.files);
        }

        const libNewDataList = require(this.espoPath + '/frontend/libs.json')
            .filter(item => !item.bundle);

        const bundledNewDataList = require(this.espoPath + '/frontend/libs.json')
            .filter(item => item.bundle)
            .filter(item => item.src || item.files);

        const resolveItemDest = item =>
            item.dest || 'client/lib/' + item.src.split('/').pop();

        /*const resolveBundledItemDest = item => {
            if (item.amdId) {
                return `client/lib/original/${item.amdId}.js`;
            }

            return 'client/lib/original/' + item.src.split('/').pop();
        };*/

        const resolveItemName = item => {
            if (item.name) {
                return item.name;
            }

            const src = /** @type string */item.src;

            if (!src) {
                throw new Error("Bad lib data in `frontend/libs.json`.");
            }

            const name = src.split('/')[1];

            if (!name) {
                throw new Error("Bad lib data in `frontend/libs.json`.");
            }

            if (name.startsWith('@')) {
                return src.split('/').slice(1, 3).join('/');
            }

            return name;
        };

        const changedLibList = [];
        const currentLibList = [];

        const changedBundledList = [];
        const currentBundledList = [];

        const toMinifyOldMap = {};
        const libOldDataMap = {};
        const bundledOldDataMap = {};

        libOldDataList.forEach(item => {
            const name = resolveItemName(item);

            toMinifyOldMap[name] = item.minify || false;
            libOldDataMap[name] = item;
        });

        bundledOldDataList.forEach(item => {
            const name = resolveItemName(item);

            bundledOldDataMap[name] = item;
        })

        libNewDataList.forEach(item => {
            const name = resolveItemName(item);

            const minify = item.minify || false;

            if (!depsNew[name]) {
                throw new Error("Not installed lib '" + name + "' `frontend/libs.json`.");
            }

            currentLibList.push(name);

            const isAdded = !(name in depsOld);

            const versionNew = depsNew[name].version || null;
            const versionOld = (depsOld[name] || {}).version || null;

            const wasMinified = (toMinifyOldMap || {})[name];

            const isDefsChanged = libOldDataMap[name] ?
                JSON.stringify(item) !== JSON.stringify(libOldDataMap[name]) :
                false;

            if (
                !isAdded &&
                versionNew === versionOld &&
                minify === wasMinified &&
                !isDefsChanged
            ) {
                return;
            }

            changedLibList.push(name);

            if (item.files) {
                item.files.forEach(item =>
                    data.filesToCopy.push(resolveItemDest(item))
                );

                if (minify) {
                    item.files.forEach(item => {
                        data.filesToCopy.push(resolveItemDest(item) + '.map');
                        data.filesToCopy.push(
                            buildUtils.destToOriginalDest(resolveItemDest(item))
                        );
                    });

                }

                return;
            }

            data.filesToCopy.push(resolveItemDest(item));

            if (minify) {
                data.filesToCopy.push(resolveItemDest(item) + '.map');
                data.filesToCopy.push(
                    buildUtils.destToOriginalDest(resolveItemDest(item))
                );
            }
        });

        libOldDataList.forEach(item => {
            const name = resolveItemName(item);

            const minify = item.minify || false;

            let toRemove = false;

            if (
                ~changedLibList.indexOf(name) ||
                !~currentLibList.indexOf(name)
            ) {
                toRemove = true;
            }

            if (!toRemove) {
                return;
            }

            if (item.files) {
                item.files.forEach(item =>
                    data.filesToDelete.push(resolveItemDest(item))
                );

                if (minify) {
                    item.files.forEach(item => {
                        data.filesToDelete.push(resolveItemDest(item) + '.map');
                        data.filesToDelete.push(
                            buildUtils.destToOriginalDest(resolveItemDest(item))
                        );
                    });
                }

                return;
            }

            data.filesToDelete.push(resolveItemDest(item));

            if (minify) {
                data.filesToDelete.push(resolveItemDest(item) + '.map');
                data.filesToDelete.push(
                    buildUtils.destToOriginalDest(resolveItemDest(item))
                );
            }
        });

        bundledNewDataList.forEach(item => {
            const name = resolveItemName(item);

            if (!depsNew[name]) {
                throw new Error("Not installed lib '" + name + "' `frontend/libs.json`.");
            }

            currentBundledList.push(name);

            const isAdded = !(name in depsOld);

            const versionNew = depsNew[name].version || null;
            const versionOld = (depsOld[name] || {}).version || null;

            const isDefsChanged = libOldDataMap[name] ?
                JSON.stringify(item) !== JSON.stringify(libOldDataMap[name]) :
                false;

            if (
                !isAdded &&
                versionNew === versionOld &&
                !isDefsChanged
            ) {
                return;
            }

            changedBundledList.push(name);

            /*if (item.files) {
                item.files.forEach(item =>
                    data.filesToCopy.push(resolveBundledItemDest(item))
                );

                return;
            }

            if (!item.src) {
                return;
            }

            data.filesToCopy.push(resolveBundledItemDest(item));*/
        });

        bundledOldDataList.forEach(item => {
            const name = resolveItemName(item);

            let toRemove = false;

            if (
                ~changedBundledList.indexOf(name) ||
                !~currentBundledList.indexOf(name)
            ) {
                toRemove = true;
            }

            if (!toRemove) {
                // noinspection UnnecessaryReturnStatementJS
                return;
            }

            /*if (item.files) {
                item.files.forEach(item =>
                    data.filesToDelete.push(resolveBundledItemDest(item))
                );

                return;
            }

            if (!item.src) {
                return;
            }

            data.filesToDelete.push(resolveBundledItemDest(item));*/
        });

        return data;
    }

    _getVersionAllFileList(version) {
        const output = cp.execSync("git show " + version + " --format=%H").toString();
        const commitHash = output.trim().split("\n")[3];

        const list = [];

        cp.execSync("git ls-tree -r " + commitHash + " --name-only")
            .toString()
            .trim()
            .split('\n')
            .forEach(file => {
                if (file === '') {
                    return;
                }

                list.push(file);
            });

        return list;
    }

    _processVendor(dto) {
        const versionFrom = dto.versionFrom;
        const currentPath = dto.currentPath;
        const tempFolderPath = dto.tempFolderPath;
        const upgradePath = dto.upgradePath;

        const output = cp.execSync("git show " + versionFrom + " --format=%H").toString();
        const commitHash = output.trim().split("\n")[3];

        if (!commitHash) {
            throw new Error("Couldn't find commit hash.");
        }

        const composerLockOldContents = cp.execSync("git show " + commitHash + ":composer.lock").toString();
        const composerOldContents = cp.execSync("git show " + commitHash + ":composer.json").toString();
        let composerLockNewContents = cp.execSync("cat " + currentPath + "/composer.lock").toString();
        let composerNewContents = cp.execSync("cat " + currentPath + "/composer.json").toString();

        composerNewContents = composerNewContents.replace(/(\r\n)/g, '\n');
        composerLockNewContents = composerLockNewContents.replace(/(\r\n)/g, '\n');

        if (
            composerLockNewContents === composerLockOldContents &&
            composerOldContents === composerNewContents
        ) {
            return;
        }

        const newPackages = JSON.parse(composerLockNewContents).packages;
        const oldPackages = JSON.parse(composerLockOldContents).packages;

        fs.mkdirSync(tempFolderPath);
        fs.mkdirSync(tempFolderPath + '/new');

        const vendorPath = tempFolderPath + '/new/vendor/';

        cp.execSync(`cp -r ${currentPath}/dev ${tempFolderPath}/new/dev`);

        fs.writeFileSync(tempFolderPath + '/new/composer.lock', composerLockNewContents);
        fs.writeFileSync(tempFolderPath + '/new/composer.json', composerNewContents);

        cp.execSync(
            "composer install --no-dev --ignore-platform-reqs",
            {
                cwd: tempFolderPath + "/new",
                stdio: 'ignore',
            }
        );

        fs.mkdirSync(upgradePath + '/vendorFiles');

        cp.execSync("mv " + vendorPath + "/autoload.php " + upgradePath + "/vendorFiles/autoload.php");
        cp.execSync("mv " + vendorPath + "/composer " + upgradePath + "/vendorFiles/composer");
        cp.execSync("mv " + vendorPath + "/bin "+ upgradePath + "/vendorFiles/bin");

        const folderList = [];

        for (const item of newPackages) {
            const name = item.name;

            if (name.indexOf('composer/') === 0) {
                continue;
            }

            let isFound = false;
            let toAdd = false;

            for (const oItem of oldPackages) {
                if (oItem.name !== name) {
                    continue;
                }

                isFound = true;

                if (item.version !== oItem.version) {
                     toAdd = true;
                }
            }

            if (!isFound) {
                toAdd = true;
            }

            if (toAdd) {
                const folder = name.split('/')[0];

                if (!~folderList.indexOf(folder)) {
                    folderList.push(folder);
                }
            }
        }

        for (const folder of folderList) {
            this._deleteGitFolderInVendor(vendorPath + '/' + folder);

            if (fs.existsSync(vendorPath + '/'+ folder)) {
                cp.execSync(
                    'mv ' + vendorPath + '/' + folder + " " + upgradePath + '/vendorFiles/' + folder
                );
            }
        }

        deleteDirRecursively(tempFolderPath);
    }

    _processArchive(dto) {
        const zipPath = dto.zipPath;
        const upgradePath = dto.upgradePath;
        const upgradeName = dto.upgradeName;

        return new Promise(resolve => {
            const zipOutput = fs.createWriteStream(zipPath);

            const archive = archiver('zip');

            archive.on('error', err => {
                throw err;
            });

            zipOutput.on('close', () => {
                console.log("Upgrade package has been built: " + upgradeName + "");

                deleteDirRecursively(upgradePath);

                resolve();
            });

            archive.directory(upgradePath, false).pipe(zipOutput);
            archive.finalize();
        });
    }
}

function deleteDirRecursively(path) {
    if (fs.existsSync(path) && fs.lstatSync(path).isDirectory()) {
        fs.readdirSync(path).forEach(file => {
            const curPath = path + "/" + file;

            if (fs.lstatSync(curPath).isDirectory()) {
                deleteDirRecursively(curPath);
            }
            else {
                fs.unlinkSync(curPath);
            }
        });

        fs.rmdirSync(path);

        return;
    }

    if (fs.existsSync(path) && fs.lstatSync(path).isFile()) {
        fs.unlinkSync(path);
    }
}

function execute(command, callback) {
    exec(command, (error, stdout) => callback(stdout));
}

module.exports = Diff;
