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
const {globSync} = require('glob');

class TemplateBundler {

    /**
     * @param {{
     *     dirs?: string[],
     *     dest?: string,
     *     clientDir?: string,
     * }} config
     */
    constructor(config) {
        this.dirs = config.dirs ?? ['client/res/templates'];
        this.dest = config.dest ?? 'client/lib/templates.tpl';
        this.clientDir = config.clientDir ?? 'client';
    }

    /**
     * @return {string}
     */
    process() {
        /** @type {string[]} */
        let allFiles = [];

        this.dirs.forEach(dir => {
            let files = globSync(dir + '/**/*.tpl')
                .map(file => file.replaceAll('\\', '/'));

            allFiles.push(...files);
        });

        let contents = [];

        allFiles.forEach(file => {
            let content = fs.readFileSync(file, 'utf-8');

            if (file.indexOf(this.clientDir) !== 0) {
                throw new Error(`File ${file} not in the client dir.`);
            }

            let path = file.slice(this.clientDir.length + 1);

            content = path + '\n' + content;

            contents.push(content);
        });

        let delimiter = this.#generateDelimiter(contents);

        let result = delimiter + '\n' + contents.join('\n' + delimiter + '\n');

        fs.writeFileSync(this.dest, result, 'utf8');

        console.log(`  ${contents.length} templates bundled in ${this.dest}.`);
    }

    /**
     * @param {string[]} contents
     * @return {string}
     */
    #generateDelimiter(contents) {
        let delimiter = '_delimiter_' + Math.random().toString(36).slice(2);

        let retry = false;

        for (let content of contents) {
            if (content.includes(delimiter)) {
                retry = true;

                break;
            }
        }

        if (retry) {
            return this.#generateDelimiter(contents);
        }

        return delimiter;
    }
}

module.exports = TemplateBundler;
