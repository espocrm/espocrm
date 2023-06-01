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

const typescript = require('typescript');
const fs = require('fs');
const {globSync} = require('glob');

/**
 * Normalizes and concatenates Espo modules.
 *
 * Modules dependent on not bundled libs are ignored. Modules dependent on such modules
 * are ignored as well and so on.
 */
class Bundler {

    /**
     * @private
     * @type {string}
     */
    basePath = 'client/src'

    /**
     * Bundles Espo js files into chunks.
     *
     * @param {{
     *   files: string[],
     *   patterns: string[],
     *   allPatterns: string[],
     *   chunkNumber?: number,
     *   libs: {
     *     src?: string,
     *     bundle?: boolean,
     *     key?: string,
     *   }[],
     * }} params
     * @return {string[]}
     */
    bundle(params) {
        let chunkNumber = params.chunkNumber || 1;

        let files = []
            .concat(params.files)
            .concat(this.#obtainFiles(params.patterns, params.files));

        let allFiles = this.#obtainFiles(params.allPatterns);

        let ignoreLibs = params.libs
            .filter(item => item.key && !item.bundle)
            .map(item => 'lib!' + item.key);

        let sortedFiles = this.#sortFiles(files, allFiles, ignoreLibs);

        let portions = [];
        let portionSize = Math.floor(sortedFiles.length / chunkNumber);

        for (let i = 0; i < chunkNumber; i++) {
            let end = i === chunkNumber - 1 ?
                sortedFiles.length :
                (i + 1) * portionSize;

            portions.push(sortedFiles.slice(i * portionSize, end));
        }

        let chunks = [];

        portions.forEach(portion => {
            let chunk = '';

            portion.forEach(file => chunk += this.normalizeSourceFile(file));

            chunks.push(chunk);
        });

        return chunks;
    }

    /**
     * @param {string[]} patterns
     * @param {string[]} [ignoreFiles]
     * @return {string[]}
     */
    #obtainFiles(patterns, ignoreFiles) {
        let files = [];
        ignoreFiles = ignoreFiles || [];

        patterns.forEach(pattern => {
            let itemFiles = globSync(pattern, {})
                .map(file => file.replaceAll('\\', '/'))
                .filter(file => !ignoreFiles.includes(file));

            files = files.concat(itemFiles);
        });

        return files;
    }

    /**
     * @param {string[]} files
     * @param {string[]} allFiles
     * @param {string[]} ignoreLibs
     * @return {string[]}
     */
    #sortFiles(files, allFiles, ignoreLibs) {
        /** @var {Object.<string, string[]>} */
        let map = {};

        let standalonePathList = [];

        let modules = [];
        let moduleFileMap = {};

        allFiles.forEach(file => {
            let data = this.#obtainModuleData(file);

            let isTarget = files.includes(file);

            if (!data) {
                if (isTarget) {
                    standalonePathList.push(file);
                }

                return;
            }

            map[data.name] = data.deps;
            moduleFileMap[data.name] = file;

            if (isTarget) {
                modules.push(data.name);
            }
        });

        let depModules = [];

        modules
            .forEach(name => {
                let deps = this.#obtainAllDeps(name, map);

                deps
                    .filter(item => !item.includes('!'))
                    .filter(item => !modules.includes(item))
                    .filter(item => !depModules.includes(item))
                    .forEach(item => {
                        depModules.push(item);
                    });
            });

        modules = modules.concat(depModules);

        /** @var {string[]} */
        let discardedModules = [];
        /** @var {Object.<string, number>} */
        let depthMap = {};

        for (let name of modules) {
            this.#buildTreeItem(
                name,
                map,
                depthMap,
                ignoreLibs,
                discardedModules
            );
        }

        modules.sort((v1, v2) => {
            return depthMap[v2] - depthMap[v1];
        });

        modules = modules.filter(item => !discardedModules.includes(item));

        let modulePaths = modules.map(name => {
            return moduleFileMap[name];
        });

        return standalonePathList.concat(modulePaths);
    }

    /**
     * @param {string} name
     * @param {Object.<string, string[]>} map
     * @param {string[]} [list]
     */
    #obtainAllDeps(name, map, list) {
        if (!list) {
            list = [];
        }

        let deps = map[name] || [];

        deps.forEach(depName => {
            if (!list.includes(depName)) {
                list.push(depName);
            }

            if (depName.includes('!')) {
                return;
            }

            this.#obtainAllDeps(depName, map, list);
        });

        return list;
    }

    /**
     * @param {string} name
     * @param {Object.<string, string[]>} map
     * @param {Object.<string, number>} depthMap
     * @param {string[]} ignoreLibs
     * @param {string[]} discardedModules
     * @param {number} [depth]
     * @param {string[]} [path]
     */
    #buildTreeItem(
        name,
        map,
        depthMap,
        ignoreLibs,
        discardedModules,
        depth,
        path
    ) {
        /** @var {string[]} */
        let deps = map[name] || [];
        depth = depth || 0;
        path = [].concat(path || []);

        path.push(name);

        if (!(name in depthMap)) {
            depthMap[name] = depth;
        }
        else if (depth > depthMap[name]) {
            depthMap[name] = depth;
        }

        if (deps.length === 0) {
            return;
        }

        for (let depName of deps) {
            if (ignoreLibs.includes(depName)) {
                path
                    .filter(item => !discardedModules.includes(item))
                    .forEach(item => discardedModules.push(item));

                return;
            }
        }

        deps.forEach(depName => {
            if (depName.includes('!')) {
                return;
            }

            this.#buildTreeItem(
                depName,
                map,
                depthMap,
                ignoreLibs,
                discardedModules,
                depth + 1,
                path
            );
        });
    }

    /**
     * @param {string} path
     * @return {{deps: string[], name: string}|null}
     */
    #obtainModuleData(path) {
        if (!this.#isClientJsFile(path)) {
            return null;
        }

        let tsSourceFile = typescript.createSourceFile(
            path,
            fs.readFileSync(path, 'utf-8'),
            typescript.ScriptTarget.Latest
        );

        let rootStatement = tsSourceFile.statements[0];

        if (
            !rootStatement.expression ||
            !rootStatement.expression.expression ||
            rootStatement.expression.expression.escapedText !== 'define'
        ) {
            return null;
        }

        let moduleName = path.slice(this._getBathPath().length, -3);

        let deps = [];

        let argumentList = rootStatement.expression.arguments;

        for (let argument of argumentList.slice(0, 2)) {
            if (argument.elements) {
                argument.elements.forEach(node => {
                    if (!node.text) {
                        return;
                    }

                    deps.push(node.text);
                });
            }
        }

        return {
            name: moduleName,
            deps: deps,
        };
    }

    /**
     * @param {string} path
     * @return {boolean}
     */
    #isClientJsFile(path) {
        return path.indexOf(this._getBathPath()) === 0 && path.slice(-3) === '.js';
    }

    /**
     * @private
     * @param {string} path
     * @return {string}
     */
    normalizeSourceFile(path) {
        let sourceCode = fs.readFileSync(path, 'utf-8');
        let basePath = this._getBathPath();

        if (!this.#isClientJsFile(path)) {
            return sourceCode;
        }

        let moduleName = path.slice(basePath.length, -3);

        let tsSourceFile = typescript.createSourceFile(
            path,
            sourceCode,
            typescript.ScriptTarget.Latest
        );

        let rootStatement = tsSourceFile.statements[0];

        if (
            !rootStatement.expression ||
            !rootStatement.expression.expression ||
            rootStatement.expression.expression.escapedText !== 'define'
        ) {
            return sourceCode;
        }

        let argumentList = rootStatement.expression.arguments;

        if (argumentList.length >= 3 || argumentList.length === 0) {
            return sourceCode;
        }

        let moduleNameNode = typescript.createStringLiteral(moduleName);

        if (argumentList.length === 1) {
            argumentList.unshift(
                typescript.createArrayLiteral([])
            );
        }

        argumentList.unshift(moduleNameNode);

        return typescript.createPrinter().printFile(tsSourceFile);
    }

    /**
     * @private
     * @return {string}
     */
    _getBathPath() {
        let path = this.basePath;

        if (path.slice(-1) !== '/') {
            path += '/';
        }

        return path;
    }
}

module.exports = Bundler;
