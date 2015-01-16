/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
﻿// node lang.js espocrm-nl_NL.po lastRelease nl_NL

if (process.argv.length < 2) {
    throw new Error('No dir argument passed');
}

var path = require('path');
var fs = require('fs');
var nodePath = require('path');
var os = require('os');
var isWin = /^win/.test(os.platform());

var espoPath = path.dirname(fs.realpathSync(__filename)) + '';

var resLang = process.argv[2] || 'lang_LANG';

var poPath = process.argv[3] || espoPath + '/build/' + 'espocrm-' + resLang +'.po';


var deleteFolderRecursive = function (path) {
    var files = [];
    if( fs.existsSync(path) ) {
        files = fs.readdirSync(path);
        files.forEach(function(file,index){
            var curPath = path + "/" + file;
            if(fs.lstatSync(curPath).isDirectory()) { // recurse
                deleteFolderRecursive(curPath);
            } else { // delete file
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
    this.baseLanguage = 'en_US';

    var dirNames = this.dirNames = {};

    var resDirNames = this.resDirNames = {};

    var coreDir = this.espoPath + 'application/Espo/Resources/i18n/' + this.baseLanguage + '/';
    var dirs = [coreDir];
    dirNames[coreDir] = 'application/Espo/Resources/i18n/' + resLang + '/';

    this.moduleList.forEach(function (moduleName) {
        var dir = this.espoPath + 'application/Espo/Modules/' + moduleName + '/Resources/i18n/' + this.baseLanguage + '/';
        dirs.push(dir);
        dirNames[dir] = 'application/Espo/Modules/' + moduleName + '/Resources/i18n/' + resLang + '/';
    }, this);

    var installDir = this.espoPath + 'install/core/i18n/' + this.baseLanguage + '/';
    dirs.push(installDir);
    dirNames[installDir] = 'install/core/i18n/' + resLang + '/';

    this.dirs = dirs;
};

Lang.prototype.escape = function(s) {
    return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
};

Lang.prototype.run = function () {

    var translationHash = {};
    var dirs = this.dirs;

    var contents = fs.readFileSync(this.poPath, 'utf8');
    var matches = contents.match(new RegExp('msgid (\"(.*)\".*\n)+.*msgstr (\"(.*)\"(\n)?)+', 'g'));

    matches.forEach(function (part) {

        //remove line break "\n"
        part = part.replace(/"\n"/g, '');

        var res = part.match(new RegExp('msgid \"(.*)\".*\n.*msgstr \"(.*)\"'));
        translationHash[res[1]] = res[2];
    }, this);

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

            for (var key in translationHash) {

                if (!translationHash[key].trim()) {
                    continue;
                }

                var escapedKey = this.escape(key);
                contents = contents.replace(new RegExp(': *\"(' + escapedKey  + ')\"', 'g'), ': "' + translationHash[key] + '"');
            }

            for (var key in translationHash) {
                if (key.substr(0, 2) == "\\\"") {

                    if (!translationHash[key].trim()) {
                       continue;
                    }

                    var escapedKey = this.escape(key);
                     contents = contents.replace(new RegExp('(' + escapedKey.replace(/\\"/g, '"')  + ')', 'g'), '' + translationHash[key].replace(/\\"/g, '"') + '');
                }
            }

            if (fs.existsSync(resFilePath)) {
                fs.unlinkSync(resFilePath);
            }
            fs.writeFileSync(resFilePath , contents);

        }, this);

    }, this);
};

var lang = new Lang(poPath, espoPath);
lang.run();