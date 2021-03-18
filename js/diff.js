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

const exec = cp.exec;
const execSync = cp.execSync;

/**
 * Builds upgrade packages.
 */
class Diff
{
    constructor (espoPath, params) {
        this.espoPath = espoPath;
        this.params = params || {};
    }

    getTagList () {
        var dirInitial = process.cwd();

        process.chdir(this.espoPath);

        var tagsString = cp.execSync('git tag -l --sort=-v:refname').toString();
        var tagList = tagsString.trim().split("\n");

        process.chdir(dirInitial);

        return tagList;
    }

    getPreviousVersionList () {
        var dirInitial = process.cwd();

        var version = (require(this.espoPath + '/package.json') || {}).version;

        process.chdir(this.espoPath);

        var tagList = this.getTagList();

        var versionFromList = [];

        var minorVersionNumber = version.split('.')[1];
        var hotfixVersionNumber = version.split('.')[2];

        for (var i = 0; i < tagList.length; i++) {
            var tag = tagList[i];

            if (tag === '') {
                continue;
            }

            if (tag === version) {
                continue;
            }

            if (!~tag.indexOf('beta') && !~tag.indexOf('alpha')) {
                var minorVersionNumberI = tag.split('.')[1];

                if (minorVersionNumberI !== minorVersionNumber) {
                    versionFromList.push(tag);
                    break;
                }
            }
        }

        if (hotfixVersionNumber !== '0') {
            for (var i = 0; i < tagList.length; i++) {
                var tag = tagList[i];

                if (tag === version) {
                    continue;
                }

                if (!~tag.indexOf('beta') && !~tag.indexOf('alpha')) {
                    versionFromList.push(tag);

                    var patchVersionNumberI = tag.split('.')[2];

                    if (patchVersionNumberI === '0') {
                        break;
                    }
                }
            }
        }

        process.chdir(dirInitial);

        return versionFromList;
    }

    buildMultipleUpgradePackages (versionFromList) {
        var params = this.params;
        var espoPath = this.espoPath;

        async function buildMultiple () {
            for (const versionFrom of versionFromList) {
                const diff = new Diff(espoPath, params);
                await diff.buildUpgradePackage(versionFrom);
            }
        }

        buildMultiple();
    }

    versionExists (version) {
        return ~this.getTagList().indexOf(version);
    }

    buildUpgradePackage (versionFrom) {
        const params = this.params;
        const espoPath = this.espoPath;

        if (!this.versionExists(versionFrom)) {
            throw new Error('Version ' + versionFrom + ' does not exist.');
        }

        return new Promise(function (resolve) {
            var acceptedVersionName = params.acceptedVersionName || versionFrom;
            var isDev = params.isDev;
            var withVendor = params.withVendor;
            var forceScripts = params.forceScripts;

            var version = (require(espoPath + '/package.json') || {}).version;

            var composerData = require(espoPath + '/composer.json') || {};

            var currentPath = espoPath;

            var buildRelPath = 'build/EspoCRM-' + version;
            var buildPath = currentPath + '/' + buildRelPath;
            var diffFilePath = currentPath + '/build/diff';
            var diffBeforeUpgradeFolderPath = currentPath + '/build/diffBeforeUpgrade';

            var tempFolderPath = currentPath + '/build/upgradeTmp';

            var folderName = 'EspoCRM-upgrade-' + acceptedVersionName + '-to-' + version;

            var upgradePath = currentPath + '/build/' + folderName;

            var zipPath = currentPath + '/build/' + folderName + '.zip';

            var upgradeDataFolder = versionFrom + '-' + version;
            var isMinorVersion = false;

            if (versionFrom.split('.')[1] !== version.split('.')[1] || versionFrom.split('.')[0] !== version.split('.')[0]) {
                isMinorVersion = true;
                upgradeDataFolder = version.split('.')[0] + '.' + version.split('.')[1];
            }

            var upgradeDataFolderPath = currentPath + '/upgrades/' + upgradeDataFolder;
            var upgradeFolderExists = fs.existsSync(upgradeDataFolderPath);

            var upgradeData = {};

            if (upgradeFolderExists) {
                upgradeData = require(upgradeDataFolderPath + '/data.json') || {};
            }

            var beforeUpgradeFileList = upgradeData.beforeUpgradeFiles || [];

            deleteDirRecursively(diffFilePath);
            deleteDirRecursively(diffBeforeUpgradeFolderPath);
            deleteDirRecursively(upgradePath);
            deleteDirRecursively(tempFolderPath);

            if (fs.existsSync(zipPath)) {
                fs.unlinkSync(zipPath);
            }

            process.chdir(espoPath);

            execute('git rev-parse --abbrev-ref HEAD', function (branch) {
                branch = branch.trim();

                if (branch !== 'master' && branch !== 'stable' && branch.indexOf('hotfix/') !== 0) {
                    console.log('\x1b[33m%s\x1b[0m', "Warning! You are on " + branch + " branch.");
                }
            });

            if (!fs.existsSync(buildPath)) {
                throw new Error("EspoCRM is not built. You need to run 'grunt' before building an upgrade package.");
            }

            if (!fs.existsSync(upgradePath)) {
                fs.mkdirSync(upgradePath);
            }

            if (!fs.existsSync(upgradePath + '/files')) {
                fs.mkdirSync(upgradePath + '/files');
            }

            if (fs.existsSync(upgradeDataFolderPath + '/beforeUpgradeFiles')) {
                cp.execSync('cp -r ' + upgradeDataFolderPath + '/beforeUpgradeFiles ' + upgradePath + '/beforeUpgradeFiles');
            }

            if (beforeUpgradeFileList.length) {
                if (!fs.existsSync(upgradePath + '/beforeUpgradeFiles')) {
                    fs.mkdirSync(upgradePath + '/beforeUpgradeFiles');
                }
            }

            var deletedFileList = this.getDeletedFileList(versionFrom);
            var tagList = this.getTagList();

            process.chdir(buildPath);

            var fileList = [];

            var stdout = cp.execSync('git diff --name-only ' + versionFrom).toString();

            (stdout || '').trim().split('\n').forEach(function (file) {
                if (file == '') {
                    return;
                }
                fileList.push(file);
            });

            fileList.push('client/espo.min.js');
            fileList.push('client/espo.min.js.map');

            fs.readdirSync('client/css/espo/').forEach(function (file) {
                fileList.push('client/css/espo/' + file);
            });

            fs.writeFileSync(diffFilePath, fileList.join('\n'));

            if (beforeUpgradeFileList.length) {
                fs.writeFileSync(diffBeforeUpgradeFolderPath, beforeUpgradeFileList.join('\n'));
            }

            if (beforeUpgradeFileList.length) {
                cp.execSync(
                    'xargs -a ' + diffBeforeUpgradeFolderPath + ' cp -p --parents -t ' + upgradePath + '/beforeUpgradeFiles'
                );
            }

            if (!isDev || forceScripts) {
                if (fs.existsSync(upgradeDataFolderPath + '/scripts')) {
                    cp.execSync('cp -r ' + upgradeDataFolderPath + '/scripts ' + upgradePath + '/scripts');
                }
            }

            execute('xargs -a ' + diffFilePath + ' cp -p --parents -t ' + upgradePath + '/files' , function (stdout) {
                var d = new Date();

                var monthN = ((d.getMonth() + 1).toString());
                monthN = monthN.length == 1 ? '0' + monthN : monthN;

                var dateN = d.getDate().toString();
                dateN = dateN.length == 1 ? '0' + dateN : dateN;

                var date = d.getFullYear().toString() + '-' + monthN + '-' + dateN.toString();

                var versionList = [];

                tagList.forEach(function (tag) {
                    if (tag == versionFrom) {
                        versionList.push(tag);
                    }

                    if (!tag || tag == version) {
                        return;
                    }
                });

                if (isDev) {
                    versionList = [];
                }

                var name = acceptedVersionName + " to " + version;

                var manifestData = {
                    "name": "EspoCRM Upgrade "+name,
                    "type": "upgrade",
                    "version": version,
                    "acceptableVersions": versionList,
                    "php": [composerData.require.php],
                    "releaseDate": date,
                    "author": "EspoCRM",
                    "description": "",
                    "delete": deletedFileList,
                };

                var additionalManifestData = upgradeData.manifest || {};
                for (var item in additionalManifestData) {
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

                new Promise(function (resolve) {
                    if (!withVendor) {
                        resolve();

                        return;
                    }

                    var output = cp.execSync("git show "+versionFrom+" --format=%H").toString();
                    var commitHash = output.trim().split("\n")[3];

                    if (!commitHash) {
                        throw new Error("Couldn't find commit hash.");
                    }

                    var composerLockOldContents = cp.execSync("git show "+commitHash+":composer.lock").toString();
                    var composerLockNewContents = cp.execSync("cat "+currentPath+"/composer.lock").toString();
                    var composerNewContents = cp.execSync("cat "+currentPath+"/composer.json").toString();

                    if (composerLockNewContents === composerLockOldContents) {
                        resolve();

                        return;
                    }

                    var newPackages = JSON.parse(composerLockNewContents).packages;
                    var oldPackages = JSON.parse(composerLockOldContents).packages;

                    fs.mkdirSync(tempFolderPath);
                    fs.mkdirSync(tempFolderPath + "/new");

                    var vendorPath = tempFolderPath + "/new/vendor/";

                    fs.writeFileSync(tempFolderPath + "/new/composer.lock", composerLockNewContents);
                    fs.writeFileSync(tempFolderPath + "/new/composer.json", composerNewContents);

                    cp.execSync("composer install --no-dev --ignore-platform-reqs", {cwd: tempFolderPath + "/new", stdio: 'ignore'});

                    fs.mkdirSync(upgradePath + '/vendorFiles');

                    cp.execSync("mv " + vendorPath + "/autoload.php "+ upgradePath + "/vendorFiles/autoload.php");
                    cp.execSync("mv " + vendorPath + "/composer "+ upgradePath + "/vendorFiles/composer");
                    cp.execSync("mv " + vendorPath + "/bin "+ upgradePath + "/vendorFiles/bin");

                    var folderList = [];

                    for (var item of newPackages) {
                        var name = item.name;

                        if (name.indexOf('composer/') === 0) {
                            continue;
                        }

                        var isFound = false;
                        var toAdd = false;

                        for (var oItem of oldPackages) {
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
                            var folder = name.split('/')[0];

                            if (!~folderList.indexOf(folder)) {
                                folderList.push(folder);
                            }
                        }
                    }

                    for (var folder of folderList) {
                        this.deleteGitFolderInVendor(vendorPath + '/' + folder);

                        if (fs.existsSync(vendorPath + '/'+ folder)) {
                            cp.execSync("mv " + vendorPath + '/'+ folder+" "+ upgradePath + '/vendorFiles/' + folder);
                        }
                    }

                    deleteDirRecursively(tempFolderPath);

                    resolve();

                }.bind(this))
                .then(function () {
                    var zipOutput = fs.createWriteStream(zipPath);

                    var archive = archiver('zip');

                    archive.on('error', function (err) {
                        throw err;
                    });

                    zipOutput.on('close', function () {
                        console.log("Upgrade package has been built: "+name+"");
                        deleteDirRecursively(upgradePath);
                        resolve();
                    });

                    archive.directory(upgradePath, false).pipe(zipOutput);

                    archive.finalize();
                });
            }.bind(this));
        }.bind(this));
    }

    getDeletedFileList (versionFrom) {
        var dirInitial = process.cwd();

        process.chdir(this.espoPath);

        var deletedFileList = this.getRepositoryDeletedFileList(versionFrom);
        var previousAllFileList = this.getPreviousAllFileList(versionFrom);
        var actualAllFileList = this.getActualAllFileList();

        previousAllFileList.forEach(function (file) {
            if (
                ! ~actualAllFileList.indexOf(file) &&
                ! ~deletedFileList.indexOf(file)
            ) {
                deletedFileList.push(file);
            }
        });

        deletedFileList = deletedFileList.filter(function (item) {
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

    getRepositoryDeletedFileList (versionFrom) {
        var deletedFileList = [];

        var stdout = cp.execSync('git diff --name-only --diff-filter=D ' + versionFrom).toString();

        (stdout || '').trim().split('\n').forEach(function (file) {
            if (file == '') {
                return;
            }

            deletedFileList.push(file);
        });

        return deletedFileList;
    }

    getActualAllFileList () {
        var actualAllFileList = [];

        var stdout = cp.execSync('git ls-tree -r --name-only HEAD').toString();

        (stdout || '').trim().split('\n').forEach(function (file) {
            if (file == '') {
                return;
            }

            actualAllFileList.push(file);
        });

        return actualAllFileList;
    }

    getPreviousAllFileList (versionFrom) {
        var previousAllFileList = [];

        var stdout = cp.execSync('git ls-tree -r --name-only ' + versionFrom).toString();

        (stdout || '').trim().split('\n').forEach(function (file) {
            if (file == '') {
                return;
            }

            previousAllFileList.push(file);
        });

        return previousAllFileList;
    }

    deleteGitFolderInVendor (dir) {
        var folderList = fs.readdirSync(dir, {withFileTypes: true})
            .filter(dirent => dirent.isDirectory())
            .map(dirent => dirent.name);

        folderList.forEach(function (folder) {
            var path = dir + '/' + folder;

            var gitPath = path + '/.git';

            if (fs.existsSync(gitPath)) {
                deleteDirRecursively(gitPath);
            }
        });
    }
}

var deleteDirRecursively = function (path) {
    if (fs.existsSync(path) && fs.lstatSync(path).isDirectory()) {
        fs.readdirSync(path).forEach(function (file, index) {
            var curPath = path + "/" + file;

            if (fs.lstatSync(curPath).isDirectory()) {
                deleteDirRecursively(curPath);
            } else {
                fs.unlinkSync(curPath);
            }
        });

        fs.rmdirSync(path);
    }
    else if (fs.existsSync(path) && fs.lstatSync(path).isFile()) {
        fs.unlinkSync(path);
    }
};

function execute (command, callback) {
    exec(command, function (error, stdout, stderr) {
        callback(stdout);
    });
};

module.exports = Diff;
