/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/**
* Builds a PO file for a specific language or all languages.
*/
class PO
{
    constructor (espoPath, language, onlyModuleName) {
        this.espoPath = espoPath;
        this.onlyModuleName = onlyModuleName;

        this.espoPath = espoPath;
        if (this.espoPath.substr(-1) != '/') {
            this.espoPath += '/';
        }

        this.moduleList = ['Crm'];
        if (onlyModuleName) {
            this.moduleList = [onlyModuleName];
        }
        this.baseLanguage = 'en_US';
        this.language = language || this.baseLanguage;

        this.outputFileName = 'espocrm-' + this.language ;
        if (onlyModuleName) {
            this.outputFileName += '-' + onlyModuleName;
        }
        this.outputFileName += '.po';

        var dirs = [
            this.espoPath + 'application/Espo/Resources/i18n/',
            this.espoPath + 'install/core/i18n/',
            this.espoPath + 'application/Espo/Core/Templates/i18n/'
        ];

        if (onlyModuleName) {
            dirs = [];
        }

        this.moduleList.forEach(function (moduleName) {
            dirs.push(this.espoPath + 'application/Espo/Modules/' + moduleName + '/Resources/i18n/');
        }, this);

        this.dirs = dirs;

        this.poContentHeader = 'msgid ""\n' +
            'msgstr ""\n' +
            '"Project-Id-Version: \\n"\n' +
            '"POT-Creation-Date: \\n"\n' +
            '"PO-Revision-Date: \\n"\n' +
            '"Last-Translator: \\n"\n' +
            '"Language-Team: EspoCRM <infobox@espocrm.com>\\n"\n' +
            '"MIME-Version: 1.0\\n"\n' +
            '"Content-Type: text/plain; charset=UTF-8\\n"\n' +
            '"Content-Transfer-Encoding: 8bit\\n"\n' +
            '"Language: ' + this.language + '\\n"\n\n';
    }

    runAll () {
        var pathToLanguage = this.espoPath + '/application/Espo/Resources/i18n/';

        var languageList = [];

        fs.readdirSync(pathToLanguage).forEach(function (dir) {
            if (dir.indexOf('_') == 2) {
                languageList.push(dir);
            }
        });

        languageList.forEach(function (language) {
            var po = new PO(this.espoPath, language, this.onlyModuleName);
            po.run();
        }, this);
    }

    run () {
        var dirs = this.dirs;
        var messageData = {};
        var targetMessageData = {}

        var poContents = this.poContentHeader;

        dirs.forEach(function (path) {
            var dirPath = this.getDirPath(path, this.baseLanguage);

            var list = fs.readdirSync(dirPath);

            list.forEach(function (fileName) {
                var filePath = this.getDirPath(path, this.baseLanguage) + fileName;
                this.populateMessageDataFromFile(filePath, messageData);

                if (this.language != this.baseLanguage) {
                    var langFilePath = this.getDirPath(path, this.language) + fileName;
                    this.populateMessageDataFromFile(langFilePath, targetMessageData);
                }
            }, this);
        }, this);


        if (this.language == this.baseLanguage) {
            targetMessageData = messageData;
        }

        for (var key in messageData) {
            poContents += 'msgctxt "' + messageData[key].context + '"\n';
            poContents += 'msgid "' + messageData[key].value + '"\n';
            var translatedValue = (targetMessageData[key] || {}).value || "";
            poContents += 'msgstr "' + translatedValue + '"\n\n';
        }

        var resFilePath = this.espoPath + 'build/' + this.outputFileName;

        if (fs.existsSync(resFilePath)) {
            fs.unlinkSync(resFilePath);
        }

        fs.writeFileSync(resFilePath, poContents);
    }

    populateMessageDataFromFile (filePath, messageData) {
        if (!fs.existsSync(filePath)) {
            return messageData;
        }

        var data = fs.readFileSync(filePath, 'utf8');
        data = JSON.parse(data);

        var fileName = filePath.split('\/').slice(-1).pop().split('.')[0];

        this.populateMessageData(fileName, data, '', messageData);
    }

    getDirPath (path, language) {
        var dirPath = path + language + '/';
        return dirPath;
    }

    populateMessageData (fileName, dataObject, prefix, messageData) {
        prefix = prefix || '';

        for (var index in dataObject) {
            if (dataObject[index] === null || dataObject[index] === "") {
                continue;
            }

            if (typeof dataObject[index] === 'object' && !Array.isArray(dataObject[index])) {
                var nextPrefix = prefix + (prefix ? '.' : '') + index;
                this.populateMessageData(fileName, dataObject[index], nextPrefix, messageData);
                continue;
            }

            var path = fileName + '.' + prefix;
            var key = path + '.' + index;
            var value = dataObject[index];

            if (Array.isArray(value)) {
                value = '"' + value.join('", "') + '"';
                path = path + '.' + index;
            }

            messageData[key] = {
                context: path,
                value: this.fixString(value)
            };
        }
    }

    replaceAll (string, find, replace) {
        var escapedRegExp = find.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
        return string.replace(new RegExp(escapedRegExp, 'g'), replace);
    }

    fixString (savedString) {
        savedString = this.replaceAll(savedString, "\\", '\\\\');
        savedString = this.replaceAll(savedString, '"', '\\"');
        savedString = this.replaceAll(savedString, "\n", '\\n');
        savedString = this.replaceAll(savedString, "\t", '\\t');
        return savedString;
    }
}

module.exports = PO;
