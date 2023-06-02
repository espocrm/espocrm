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
     * @param {Object.<string, string>} modulePaths
     */
    constructor(modulePaths) {
        this.modulePaths = modulePaths;
    }

    /**
     * @private
     * @type {string}
     */
    basePath = 'client/src'

    /**
     * Bundles Espo js files into chunks.
     *
     * @param {{
     *   files?: string[],
     *   patterns: string[],
     *   allPatterns?: string[],
     *   ignoreFiles?: string[],
     *   dependentOn?: string[],
     *   libs: {
     *     src?: string,
     *     bundle?: boolean,
     *     key?: string,
     *   }[],
     * }} params
     * @return {{
     *   contents: string,
     *   files: string[],
     *   modules: string[],
     *   notBundledModules: string[],
     * }}
     */
    bundle(params) {
        let files = []
            .concat(params.files || [])
            .concat(this.#obtainFiles(params.patterns, params.files))
            .filter(file => !params.ignoreFiles.includes(file));

        let allFiles = this.#obtainFiles(params.allPatterns || params.patterns);

        let ignoreLibs = params.libs
            .filter(item => item.key && !item.bundle)
            .map(item => 'lib!' + item.key)
            .filter(item => !(params.dependentOn || []).includes(item));

        let notBundledModules = [];

        let sortedFiles = this.#sortFiles(
            files,
            allFiles,
            ignoreLibs,
            params.ignoreFiles || [],
            notBundledModules,
            params.dependentOn || null,
        );

        let contents = '';

        sortedFiles.forEach(file => contents += this.#normalizeSourceFile(file));

        let modules = sortedFiles.map(file => this.#obtainModuleName(file));

        return {
            contents: contents,
            files: sortedFiles,
            modules: modules,
            notBundledModules: notBundledModules,
        };
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
     * @param {string[]} ignoreFiles
     * @param {string[]} notBundledModules
     * @param {string[]|null} dependentOn
     * @return {string[]}
     */
    #sortFiles(
        files,
        allFiles,
        ignoreLibs,
        ignoreFiles,
        notBundledModules,
        dependentOn
    ) {
        /** @var {Object.<string, string[]>} */
        let map = {};
        let standalonePathList = [];
        let modules = [];
        let moduleFileMap = {};

        let ignoreModules = ignoreFiles.map(file => this.#obtainModuleName(file));

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

        modules = modules
            .concat(depModules)
            .filter(module => !ignoreModules.includes(module));

        /** @var {string[]} */
        let discardedModules = [];
        /** @var {Object.<string, number>} */
        let depthMap = {};
        /** @var {string[]} */
        let pickedModules = [];

        for (let name of modules) {
            this.#buildTreeItem(
                name,
                map,
                depthMap,
                ignoreLibs,
                dependentOn,
                discardedModules,
                pickedModules
            );
        }

        if (dependentOn) {
            modules = pickedModules;
        }

        modules.sort((v1, v2) => {
            return depthMap[v2] - depthMap[v1];
        });

        discardedModules.forEach(item => notBundledModules.push(item));

        modules = modules.filter(item => !discardedModules.includes(item));

        let modulePaths = modules.map(name => {
            if (!moduleFileMap[name]) {
                throw Error(`Can't obtain ${name}. Might be missing in allPatterns.`);
            }

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
     * @param {string[]} dependentOn
     * @param {string[]} discardedModules
     * @param {string[]} pickedModules
     * @param {number} [depth]
     * @param {string[]} [path]
     */
    #buildTreeItem(
        name,
        map,
        depthMap,
        ignoreLibs,
        dependentOn,
        discardedModules,
        pickedModules,
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

            if (dependentOn && dependentOn.includes(depName)) {
                path
                    .filter(item => !pickedModules.includes(item))
                    .forEach(item => pickedModules.push(item));
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
                dependentOn,
                discardedModules,
                pickedModules,
                depth + 1,
                path
            );
        });
    }

    /**
     * @param {string} file
     * @return string
     */
    #obtainModuleName(file) {
        for (let mod in this.modulePaths) {
            let part = this.modulePaths[mod] + '/src/';

            if (file.indexOf(part) === 0) {
                return mod + ':' + file.substring(part.length, file.length - 3);
            }
        }

        return file.slice(this.#getBathPath().length, -3);
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

        let moduleName = this.#obtainModuleName(path);

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
        if (path.slice(-3) !== '.js') {
            return false;
        }

        let startParts = [this.#getBathPath()];

        for (let mod in this.modulePaths) {
            let modPath = this.modulePaths[mod];

            startParts.push(modPath);
        }

        for (let starPart of startParts) {
            if (path.indexOf(starPart) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @private
     * @param {string} path
     * @return {string}
     */
    #normalizeSourceFile(path) {
        let sourceCode = fs.readFileSync(path, 'utf-8');
        let basePath = this.#getBathPath();

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
    #getBathPath() {
        let path = this.basePath;

        if (path.slice(-1) !== '/') {
            path += '/';
        }

        return path;
    }
}

module.exports = Bundler;
