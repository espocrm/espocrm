/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
const sys = require('util');
const cp = require('child_process');
const archiver = require('archiver');
const process = require('process');

const exec = cp.exec;
const execSync = cp.execSync;

/**
 * Builds upgrade packages.
 */
class Diff
{
    constructor(espoPath, params) {
        this.espoPath = espoPath;
        this.params = params || {};
    }

    getTagList() {
        let dirInitial = process.cwd();

        process.chdir(this.espoPath);

        let tagsString = cp.execSync('git tag -l --sort=-v:refname').toString();
        let tagList = tagsString.trim().split("\n");

        process.chdir(dirInitial);

        return tagList;
    }

    getPreviousVersionList() {
        let dirInitial = process.cwd();

        let version = (require(this.espoPath + '/package.json') || {}).version;

        process.chdir(this.espoPath);

        let tagList = this.getTagList();

        let versionFromList = [];

        let minorVersionNumber = version.split('.')[1];
        let hotfixVersionNumber = version.split('.')[2];

        for (let i = 0; i < tagList.length; i++) {
            let tag = tagList[i];

            if (tag === '') {
                continue;
            }

            if (tag === version) {
                continue;
            }

            if (!~tag.indexOf('beta') && !~tag.indexOf('alpha')) {
                let minorVersionNumberI = tag.split('.')[1];

                if (minorVersionNumberI !== minorVersionNumber) {
                    versionFromList.push(tag);

                    break;
                }
            }
        }

        if (hotfixVersionNumber !== '0') {
            for (let i = 0; i < tagList.length; i++) {
                let tag = tagList[i];

                if (tag === version) {
                    continue;
                }

                if (!~tag.indexOf('beta') && !~tag.indexOf('alpha')) {
                    versionFromList.push(tag);

                    let patchVersionNumberI = tag.split('.')[2];

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

    versionExists(version) {
        return ~this.getTagList().indexOf(version);
    }

    buildUpgradePackage(versionFrom) {
        const params = this.params;
        let espoPath = this.espoPath;

        if (!this.versionExists(versionFrom)) {
            throw new Error('Version ' + versionFrom + ' does not exist.');
        }

        return new Promise(resolve => {
            let acceptedVersionName = params.acceptedVersionName || versionFrom;
            let isDev = params.isDev;
            let withVendor = params.withVendor;
            let forceScripts = params.forceScripts;

            let version = (require(espoPath + '/package.json') || {}).version;
            let composerData = require(espoPath + '/composer.json') || {};

            let currentPath = espoPath;
            let buildRelPath = 'build/EspoCRM-' + version;
            let buildPath = currentPath + '/' + buildRelPath;
            let diffFilePath = currentPath + '/build/diff';
            let diffBeforeUpgradeFolderPath = currentPath + '/build/diffBeforeUpgrade';

            let tempFolderPath = currentPath + '/build/upgradeTmp';
            let folderName = 'EspoCRM-upgrade-' + acceptedVersionName + '-to-' + version;
            let upgradePath = currentPath + '/build/' + folderName;
            let zipPath = currentPath + '/build/' + folderName + '.zip';
            let upgradeDataFolder = versionFrom + '-' + version;

            let isMinorVersion =
                versionFrom.split('.')[1] !== version.split('.')[1] ||
                versionFrom.split('.')[0] !== version.split('.')[0];

            if (isMinorVersion) {
                upgradeDataFolder = version.split('.')[0] + '.' + version.split('.')[1];
            }

            let upgradeDataFolderPath = currentPath + '/upgrades/' + upgradeDataFolder;
            let upgradeFolderExists = fs.existsSync(upgradeDataFolderPath);

            let upgradeData = {};

            if (upgradeFolderExists) {
                upgradeData = require(upgradeDataFolderPath + '/data.json') || {};
            }

            let beforeUpgradeFileList = upgradeData.beforeUpgradeFiles || [];

            deleteDirRecursively(diffFilePath);
            deleteDirRecursively(diffBeforeUpgradeFolderPath);
            deleteDirRecursively(upgradePath);
            deleteDirRecursively(tempFolderPath);

            if (fs.existsSync(zipPath)) {
                fs.unlinkSync(zipPath);
            }

            process.chdir(espoPath);

            this.notifyIfBadBranch();

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

            let deletedFileList = this.getDeletedFileList(versionFrom);
            let tagList = this.getTagList();

            process.chdir(buildPath);

            var fileList = [];

            var stdout = cp.execSync('git diff --name-only ' + versionFrom).toString();

            (stdout || '').trim().split('\n').forEach(file => {
                if (file === '') {
                    return;
                }

                fileList.push(file);
            });

            fileList.push('client/espo.min.js');
            fileList.push('client/espo.min.js.map');

            fs.readdirSync('client/css/espo/').forEach(file => {
                fileList.push('client/css/espo/' + file);
            });

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
                let date = this.getCurrentDate();

                let versionList = [];

                tagList.forEach(tag => {
                    if (tag === versionFrom) {
                        versionList.push(tag);
                    }

                    if (!tag || tag === version) {
                        return;
                    }
                });

                if (isDev) {
                    versionList = [];
                }

                let upgradeName = acceptedVersionName + " to " + version;

                let manifestData = {
                    "name": "EspoCRM Upgrade " + upgradeName,
                    "type": "upgrade",
                    "version": version,
                    "acceptableVersions": versionList,
                    "php": [composerData.require.php],
                    "releaseDate": date,
                    "author": "EspoCRM",
                    "description": "",
                    "delete": deletedFileList,
                };

                let additionalManifestData = upgradeData.manifest || {};

                for (let item in additionalManifestData) {
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

                if (!withVendor) {
                    this.processVendor({
                        versionFrom: versionFrom,
                        currentPath: currentPath,
                        tempFolderPath: tempFolderPath,
                        upgradePath: upgradePath,
                    });
                }

                this.processArchive({
                    upgradeName: upgradeName,
                    zipPath: zipPath,
                    upgradePath: upgradePath,
                })
                .then(() => resolve());

            });
        });
    }

    notifyIfBadBranch() {
        let currentBranch = cp.execSync('git rev-parse --abbrev-ref HEAD').toString().trim();

        if (
            currentBranch !== 'master' &&
            currentBranch !== 'stable' &&
            currentBranch.indexOf('hotfix/') !== 0
        ) {
            console.log('\x1b[33m%s\x1b[0m', "Warning! You are on " + currentBranch + " branch.");
        }
    }

    getCurrentDate() {
        let d = new Date();

        let monthN = ((d.getMonth() + 1).toString());
        monthN = monthN.length === 1 ? '0' + monthN : monthN;

        let dateN = d.getDate().toString();
        dateN = dateN.length === 1 ? '0' + dateN : dateN;

        return d.getFullYear().toString() + '-' + monthN + '-' + dateN.toString();
    }

    getDeletedFileList(versionFrom) {
        let dirInitial = process.cwd();

        process.chdir(this.espoPath);

        let deletedFileList = this.getRepositoryDeletedFileList(versionFrom);
        let previousAllFileList = this.getPreviousAllFileList(versionFrom);
        let actualAllFileList = this.getActualAllFileList();

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
                item.indexOf('frontend/less') === 0
            ) {
                return false;
            }

            return true;
        });

        process.chdir(dirInitial);

        return deletedFileList;
    }

    getRepositoryDeletedFileList(versionFrom) {
        let deletedFileList = [];

        let stdout = cp.execSync('git diff --name-only --diff-filter=D ' + versionFrom).toString();

        (stdout || '').trim().split('\n').forEach(file => {
            if (file === '') {
                return;
            }

            deletedFileList.push(file);
        });

        return deletedFileList;
    }

    getActualAllFileList() {
        let actualAllFileList = [];

        let stdout = cp.execSync('git ls-tree -r --name-only HEAD').toString();

        (stdout || '').trim().split('\n').forEach(file => {
            if (file === '') {
                return;
            }

            actualAllFileList.push(file);
        });

        return actualAllFileList;
    }

    getPreviousAllFileList(versionFrom) {
        let previousAllFileList = [];

        let stdout = cp.execSync('git ls-tree -r --name-only ' + versionFrom).toString();

        (stdout || '').trim().split('\n').forEach(file => {
            if (file === '') {
                return;
            }

            previousAllFileList.push(file);
        });

        return previousAllFileList;
    }

    deleteGitFolderInVendor(dir) {
        let folderList = fs.readdirSync(dir, {withFileTypes: true})
            .filter(dirent => dirent.isDirectory())
            .map(dirent => dirent.name);

        folderList.forEach(folder => {
            let path = dir + '/' + folder;

            let gitPath = path + '/.git';

            if (fs.existsSync(gitPath)) {
                deleteDirRecursively(gitPath);
            }
        });
    }

    processVendor(dto) {
        let versionFrom = dto.versionFrom;
        let currentPath = dto.currentPath;
        let tempFolderPath = dto.tempFolderPath;
        let upgradePath = dto.upgradePath;

        let output = cp.execSync("git show " + versionFrom + " --format=%H").toString();
        let commitHash = output.trim().split("\n")[3];

        if (!commitHash) {
            throw new Error("Couldn't find commit hash.");
        }

        let composerLockOldContents = cp.execSync("git show " + commitHash + ":composer.lock").toString();
        let composerLockNewContents = cp.execSync("cat " + currentPath + "/composer.lock").toString();
        let composerNewContents = cp.execSync("cat " + currentPath + "/composer.json").toString();

        if (composerLockNewContents === composerLockOldContents) {
            return;
        }

        let newPackages = JSON.parse(composerLockNewContents).packages;
        let oldPackages = JSON.parse(composerLockOldContents).packages;

        fs.mkdirSync(tempFolderPath);
        fs.mkdirSync(tempFolderPath + '/new');

        let vendorPath = tempFolderPath + '/new/vendor/';

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

        let folderList = [];

        for (let item of newPackages) {
            let name = item.name;

            if (name.indexOf('composer/') === 0) {
                continue;
            }

            let isFound = false;
            let toAdd = false;

            for (let oItem of oldPackages) {
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
                let folder = name.split('/')[0];

                if (!~folderList.indexOf(folder)) {
                    folderList.push(folder);
                }
            }
        }

        for (let folder of folderList) {
            this.deleteGitFolderInVendor(vendorPath + '/' + folder);

            if (fs.existsSync(vendorPath + '/'+ folder)) {
                cp.execSync(
                    'mv ' + vendorPath + '/' + folder + " " + upgradePath + '/vendorFiles/' + folder
                );
            }
        }

        deleteDirRecursively(tempFolderPath);
    }

    processArchive(dto) {
        let zipPath = dto.zipPath;
        let upgradePath = dto.upgradePath;
        let upgradeName = dto.upgradeName;

        return new Promise(resolve => {
            let zipOutput = fs.createWriteStream(zipPath);

            let archive = archiver('zip');

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
        fs.readdirSync(path).forEach((file, index) => {
            let curPath = path + "/" + file;

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

        return;
    }
}

function execute(command, callback) {
    exec(command, (error, stdout, stderr) => callback(stdout));
};

module.exports = Diff;
