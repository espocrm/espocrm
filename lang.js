/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
﻿
if (process.argv.length < 2) {
    throw new Error('No dir argument passed');
}

var path = require('path');
var fs = require('fs');
var nodePath = require('path');
var os = require('os');
var PO = require('pofile');
var isWin = /^win/.test(os.platform());

var espoPath = path.dirname(fs.realpathSync(__filename)) + '';
var resLang = process.argv[2] || 'lang_LANG';

var onlyModuleName = null;
if (process.argv.length > 2) {
    for (var i in process.argv) {
        if (~process.argv[i].indexOf('--module=')) {
            onlyModuleName = process.argv[i].substr(('--module=').length);
        }
    }
}

var poPath = espoPath + '/build/' + 'espocrm-' + resLang;
if (onlyModuleName) {
    poPath += '-' + onlyModuleName;
}
poPath += '.po';

if (process.argv.length > 2) {
    for (var i in process.argv) {
        if (~process.argv[i].indexOf('--path=')) {
            poPath = process.argv[i].substr(('--path=').length);
        }
    }
}

var deleteFolderRecursive = function (path) {
    var files = [];
    if (fs.existsSync(path)) {
        files = fs.readdirSync(path);
        files.forEach(function(file,index){
            var curPath = path + "/" + file;
            if(fs.lstatSync(curPath).isDirectory()) {
                deleteFolderRecursive(curPath);
            } else {
                fs.unlinkSync(curPath);
            }
        });
        fs.rmdirSync(path);
    }
};

function Lang (poPath, espoPath) {
    this.poPath = poPath;

    this.espoPath = espoPath;
    if (this.espoPath.substr(-1) != '/') {
        this.espoPath += '/';
    }

    this.currentPath = path.dirname(fs.realpathSync(__filename)) + '/';

    this.moduleList = ['Crm'];
    if (onlyModuleName) {
        this.moduleList = [onlyModuleName];
    }

    this.baseLanguage = 'en_US';

    var dirNames = this.dirNames = {};

    var resDirNames = this.resDirNames = {};

    var coreDir = this.espoPath + 'application/Espo/Resources/i18n/' + this.baseLanguage + '/';
    var dirs = [coreDir];
    dirNames[coreDir] = 'application/Espo/Resources/i18n/' + resLang + '/';


    var installDir = this.espoPath + 'install/core/i18n/' + this.baseLanguage + '/';
    dirs.push(installDir);
    dirNames[installDir] = 'install/core/i18n/' + resLang + '/';


    var templatesDir = this.espoPath + 'application/Espo/Core/Templates/i18n/' + this.baseLanguage + '/';
    dirs.push(templatesDir);
    dirNames[templatesDir] = 'application/Espo/Core/Templates/i18n/' + resLang + '/';

    if (onlyModuleName) {
        dirs = [];
    }

    this.moduleList.forEach(function (moduleName) {
        var dir = this.espoPath + 'application/Espo/Modules/' + moduleName + '/Resources/i18n/' + this.baseLanguage + '/';
        dirs.push(dir);
        dirNames[dir] = 'application/Espo/Modules/' + moduleName + '/Resources/i18n/' + resLang + '/';
    }, this);

    this.dirs = dirs;
};

Lang.prototype.escape = function(s) {
    return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
};

Lang.prototype.run = function () {
    var translationData = {};
    var dirs = this.dirs;

    PO.load(this.poPath, function (err, po) {
        if (err) throw new Error("Could not parse " + this.poPath);

        po.items.forEach(function (item) {
            if (!item.msgctxt) return;
            var key = item.msgctxt + '__' + item.msgid;
            var file = item.msgctxt.split('.')[0];
            var path = item.msgctxt.split('.').slice(1);

            var o = {
                stringOriginal: item.msgid,
                stringTranslated: item.msgstr[0],
                context: item.msgctxt,
                file: file,
                path: path
            };
            translationData[file] = translationData[file] || [];
            translationData[file].push(o);
        });

        dirs.forEach(function (path) {
            var resDirPath = this.dirNames[path];
            var resPath = this.currentPath + 'build/' + resLang + '/' + resDirPath;

            if (!fs.existsSync(resPath)) {
                var d = '';
                resPath.split('/').forEach(function (f) {
                    if (!f) {
                        return;
                    }
                    if (isWin) {
                        d = nodePath.join(d, f);
                    } else {
                        d += '/' + f;
                    }
                    if (!fs.existsSync(d)) {
                        fs.mkdirSync(d);
                    }
                });
            }

            var list = fs.readdirSync(path);
            list.forEach(function (fileName) {

                var filePath = path + fileName;
                var resFilePath = resPath + '/' + fileName;

                var contents = fs.readFileSync(filePath, 'utf8');

                var fileKey = fileName.split('.')[0];

                var fileObject = JSON.parse(contents);
                var targetFileObject = {};

                if (!(fileKey in translationData)) return;

                translationData[fileKey].forEach(function (item) {
                    var isArray = false;
                    var isMet = true;
                    var c = fileObject;
                    var path = item.path.slice(0);

                    for (var i in item.path) {
                        var key = item.path[i];
                        if (key in c) {
                            c = c[key];
                            if (Array.isArray(c)) {
                                isArray = true;
                                break;
                            }
                        } else {
                            isMet = false;
                        }
                    }

                    var pathList = [];

                    if (isMet) {
                        if (!isArray) {
                            var isMet = false;
                            for (var k in c) {
                                if (c[k] === item.stringOriginal) {
                                    var p = path.slice(0);
                                    p.push(k);
                                    pathList.push(p);
                                    isMet = true;
                                }
                            }
                        } else {
                            pathList.push(path);
                        }
                    }

                    if (!isMet) return;

                    var targetValue = item.stringTranslated;
                    if (targetValue === '') {
                        return;
                    } else {
                        if (item.stringOriginal === item.stringTranslated) {
                            return;
                        }
                    }
                    if (isArray) {
                        try {
                            var targetValue = JSON.parse('[' + targetValue  + ']');
                        } catch (e) {
                            targetValue = null;
                        }
                    }
                    if (targetValue == null) return;

                    pathList.forEach(function (path) {
                        var c = targetFileObject;
                        path.forEach(function (pathKey, i) {
                            if (i < path.length - 1) {
                                c[pathKey] = c[pathKey] || {};
                                c = c[pathKey];
                            } else {
                                c[pathKey] = targetValue;
                            }
                        }, this);
                    }, this);
                }, this);

                var contents = JSON.stringify(targetFileObject, null, '  ');

                if (fs.existsSync(resFilePath)) {
                    fs.unlinkSync(resFilePath);
                }
                fs.writeFileSync(resFilePath, contents);

                return;
            }, this);

        }, this);

    }.bind(this))
};

var lang = new Lang(poPath, espoPath);
lang.run();
