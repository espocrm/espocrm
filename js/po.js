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

/**
* Builds a PO file for a specific language or all languages.
*/
class PO
{
    constructor (espoPath, language, onlyModuleName) {
        this.espoPath = espoPath;
        this.onlyModuleName = onlyModuleName;

        this.espoPath = espoPath;
        if (this.espoPath.substr(-1) !== '/') {
            this.espoPath += '/';
        }

        this.moduleList = ['Crm'];

        if (onlyModuleName) {
            this.moduleList = [onlyModuleName];
        }

        this.baseLanguage = 'en_US';
        this.language = language || this.baseLanguage;

        this.outputFileName = 'espocrm-' + this.language;

        if (onlyModuleName) {
            this.outputFileName += '-' + onlyModuleName;
        }

        this.outputFileName += '.po';

        let dirs = [
            this.espoPath + 'application/Espo/Resources/i18n/',
            this.espoPath + 'install/core/i18n/',
            this.espoPath + 'application/Espo/Core/Templates/i18n/'
        ];

        if (onlyModuleName) {
            dirs = [];
        }

        this.moduleList.forEach(moduleName => {
            let path1 = this.espoPath + 'application/Espo/Modules/' + moduleName;
            let path2 = this.espoPath + 'custom/Espo/Modules/' + moduleName;

            let dir = fs.existsSync(path1) ? path1 : path2;

            dirs.push(dir + '/Resources/i18n/');
        });

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
        let pathToLanguage = this.espoPath + '/application/Espo/Resources/i18n/';

        let languageList = [];

        fs.readdirSync(pathToLanguage).forEach(dir => {
            if (dir.indexOf('_') === 2) {
                languageList.push(dir);
            }
        });

        languageList.forEach(language => {
            let po = new PO(this.espoPath, language, this.onlyModuleName);

            po.run();
        });
    }

    run () {
        let dirs = this.dirs;
        let messageData = {};
        let targetMessageData = {}

        let poContents = this.poContentHeader;

        dirs.forEach(path => {
            let dirPath = this.getDirPath(path, this.baseLanguage);

            let list = fs.readdirSync(dirPath);

            list.forEach(fileName => {
                let filePath = this.getDirPath(path, this.baseLanguage) + fileName;

                this.populateMessageDataFromFile(filePath, messageData);

                if (this.language !== this.baseLanguage) {
                    let langFilePath = this.getDirPath(path, this.language) + fileName;

                    this.populateMessageDataFromFile(langFilePath, targetMessageData);
                }
            });
        });


        if (this.language === this.baseLanguage) {
            targetMessageData = messageData;
        }

        for (let key in messageData) {
            poContents += 'msgctxt "' + messageData[key].context + '"\n';
            poContents += 'msgid "' + messageData[key].value + '"\n';

            let translatedValue = (targetMessageData[key] || {}).value || "";

            poContents += 'msgstr "' + translatedValue + '"\n\n';
        }

        let resFilePath = this.espoPath + 'build/' + this.outputFileName;

        if (fs.existsSync(resFilePath)) {
            fs.unlinkSync(resFilePath);
        }

        fs.writeFileSync(resFilePath, poContents);
    }

    populateMessageDataFromFile (filePath, messageData) {
        if (!fs.existsSync(filePath)) {
            return messageData;
        }

        let data = fs.readFileSync(filePath, 'utf8');

        data = JSON.parse(data);

        let fileName = filePath.split('\/').slice(-1).pop().split('.')[0];

        this.populateMessageData(fileName, data, '', messageData);
    }

    getDirPath (path, language) {
        return path + language + '/';
    }

    populateMessageData (fileName, dataObject, prefix, messageData) {
        prefix = prefix || '';

        for (let index in dataObject) {
            if (dataObject[index] === null || dataObject[index] === "") {
                continue;
            }

            if (typeof dataObject[index] === 'object' && !Array.isArray(dataObject[index])) {
                let nextPrefix = prefix + (prefix ? '.' : '') + index;

                this.populateMessageData(fileName, dataObject[index], nextPrefix, messageData);

                continue;
            }

            let path = fileName + '.' + prefix;
            let key = path + '.' + index;
            let value = dataObject[index];

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
        let escapedRegExp = find.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");

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
