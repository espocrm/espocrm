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

const babelCore = require("@babel/core");
const fs = require('fs');
const {globSync} = require('glob');

class Transpiler {

    /**
     * @param {{
     *     path?: string,
     *     destDir?: string,
     *     mod?: string,
     *     file?: string,
     * }} config
     */
    constructor(config) {
        this.path = (config.path ?? 'client') + '/src';
        this.destDir = config.destDir || 'client/lib/transpiled';
        this.mod = config.mod;
        this.file = config.file;

        this.contentsCache = {};
    }

    /**
     * @return {{
     *     transpiled: string[],
     *     copied: string[],
     * }}
     */
    process() {
        let allFiles = globSync(this.path + '/**/*.js')
            .map(file => file.replaceAll('\\', '/'));

        if (this.file) {
            let file = this.file.replaceAll('\\', '/');

            if (!allFiles.includes(file)) {
                return {
                    transpiled: [],
                    copied: [],
                };
            }

            allFiles = [file];
        }

        let files = allFiles.filter(file => this.#isToBeTranspiled(file));

        let otherFiles = !this.file ?
            allFiles.filter(file => !files.includes(file)) : [];

        files.forEach(file => this.#processFile(file));
        otherFiles.forEach(file => this.#copyFile(file));

        return {
            transpiled: files,
            copied: otherFiles,
        };
    }

    /**
     * @param {string} file
     */
    #processFile(file) {
        const module = this.#obtainModuleName(file);

        const result = babelCore.transformSync(this.#getContents(file), {
            plugins: ['@babel/plugin-transform-modules-amd'],
            moduleId: module,
            sourceMaps: true,
        });

        let dir = this.#obtainTargetDir(module);

        fs.mkdirSync(dir, {recursive: true});

        let part = module;

        if (part.includes(':')) {
            part = part.split(':')[1];
        }

        let filePart = part.split('/').slice(-1)[0] + '.js';
        let destFile = dir + filePart;

        let resultContent = result.code + `\n//# sourceMappingURL=${filePart}.map ;`;

        fs.writeFileSync(destFile, resultContent, 'utf-8');
        fs.writeFileSync(destFile + '.map', result.map.toString(), 'utf-8');
    }

    /**
     * @param {string} file
     */
    #copyFile(file) {
        let module = this.#obtainModuleName(file);
        let dir = this.#obtainTargetDir(module);

        fs.mkdirSync(dir, {recursive: true});

        let destFile = dir + file.split('/').slice(-1)[0];

        fs.mkdirSync(dir, {recursive: true});
        fs.copyFileSync(file, destFile);
    }

    /**
     * @param {string} module
     * @return {string}
     */
    #obtainTargetDir(module) {
        let destDir = this.destDir;

        let part = 'src';
        let path = module;

        if (module.includes(':')) {
            let [mod, itemPath] = module.split(':');

            part = 'modules/' + mod + '/' + part;

            path = itemPath;
        }

        destDir += '/' + part + '/' + path.split('/').slice(0, -1).join('/');

        if (destDir.slice(-1) !== '/') {
            destDir += '/';
        }

        return destDir;
    }

    /**
     * @param {string} file
     * @return {boolean}
     */
    #isToBeTranspiled(file) {
        let contents = this.#getContents(file);

        return !contents.includes("\ndefine(") && contents.includes("\nexport ");
    }

    /**
     * @param {string} file
     * @return {string}
     */
    #getContents(file) {
        if (!(file in this.contentsCache)) {
            this.contentsCache[file] = fs.readFileSync(file, 'utf-8');
        }

        return this.contentsCache[file];
    }

    /**
     * @param {string} file
     * @return string
     */
    #obtainModuleName(file) {
        if (this.mod) {
            return this.mod + ':' + file.slice(this.path.length + 1, -3);
        }

        return file.slice(this.path.length + 1, -3);
    }
}

module.exports = Transpiler;
