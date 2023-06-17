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
     * @param {Object.<string, string>} modPaths
     * @param {string} [basePath]
     * @param {string} [transpiledPath]
     */
    constructor(modPaths, basePath, transpiledPath) {
        this.modPaths = modPaths;
        this.basePath = basePath ?? 'client';
        this.transpiledPath = transpiledPath ?? 'client/lib/transpiled';

        this.srcPath = this.basePath + '/src';
    }

    /**
     * Bundles Espo js files into chunks.
     *
     * @param {{
     *     name: string,
     *     files?: string[],
     *     patterns: string[],
     *     ignoreFiles?: string[],
     *     lookupPatterns?: string[],
     *     ignoreFullPathFiles?: string[],
     *     dependentOn?: string[],
     *     libs: {
     *         src?: string,
     *         bundle?: boolean,
     *         key?: string,
     *     }[],
     * }} params
     * @return {{
     *     contents: string,
     *     files: string[],
     *     modules: string[],
     *     notBundledModules: string[],
     *     dependencyModules: string[],
     * }}
     */
    bundle(params) {
        let ignoreFullPathFiles = params.ignoreFullPathFiles ?? [];
        let files = params.files ?? [];
        let ignoreFiles = params.ignoreFiles ?? [];

        let fullPathFiles = []
            .concat(this.#normalizePaths(params.files || []))
            .concat(this.#obtainFiles(params.patterns, [...files, ...ignoreFiles]))
            // @todo Check if working.
            .filter(file => !ignoreFullPathFiles.includes(file));

        let allFiles = this.#obtainFiles(params.lookupPatterns || params.patterns);

        let ignoreLibs = params.libs
            .filter(item => item.key && !item.bundle)
            .map(item => 'lib!' + item.key)
            .filter(item => !(params.dependentOn || []).includes(item));

        let notBundledModules = [];

        let {files: sortedFiles, depModules} = this.#sortFiles(
            params.name,
            fullPathFiles,
            allFiles,
            ignoreLibs,
            ignoreFullPathFiles,
            notBundledModules,
            params.dependentOn || null,
        );

        let contents = '';

        this.#mapToTraspiledFiles(sortedFiles)
            .forEach(file => contents += this.#normalizeSourceFile(file) + '\n');

        let modules = sortedFiles.map(file => this.#obtainModuleName(file));

        return {
            contents: contents,
            files: sortedFiles,
            modules: modules,
            notBundledModules: notBundledModules,
            dependencyModules: depModules,
        };
    }

    /**
     * @param {string[]} files
     * @return {string[]}
     */
    #mapToTraspiledFiles(files) {
        return files.map(file => {
            return this.transpiledPath + '/' + file.slice(this.basePath.length + 1);
        });
    }

    /**
     * @param {string[]} patterns
     * @param {string[]} [ignoreFiles]
     * @return {string[]}
     */
    #obtainFiles(patterns, ignoreFiles) {
        let files = [];
        ignoreFiles = this.#normalizePaths(ignoreFiles || []);

        this.#normalizePaths(patterns).forEach(pattern => {
            let itemFiles = globSync(pattern, {})
                .map(file => file.replaceAll('\\', '/'))
                .filter(file => !ignoreFiles.includes(file));

            files = files.concat(itemFiles);
        });

        return files;
    }

    /**
     * @param {string[]} patterns
     * @return {string[]}
     */
    #normalizePaths(patterns) {
        return patterns.map(item => this.basePath + '/' + item);
    }

    /**
     * @param {string} name
     * @param {string[]} files
     * @param {string[]} allFiles
     * @param {string[]} ignoreLibs
     * @param {string[]} ignoreFiles
     * @param {string[]} notBundledModules
     * @param {string[]|null} dependentOn
     * @return {{
     *     files: string[],
     *     depModules: string[],
     * }}
     */
    #sortFiles(
        name,
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
        let allDepModules = [];

        modules
            .forEach(name => {
                let deps = this.#obtainAllDeps(name, map);

                deps
                    .filter(item => !modules.includes(item))
                    .filter(item => !allDepModules.includes(item))
                    .forEach(item => allDepModules.push(item));

                deps
                    .filter(item => !item.includes('!'))
                    .filter(item => !modules.includes(item))
                    .filter(item => !depModules.includes(item))
                    .forEach(item => depModules.push(item));
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

        for (let module of modules) {
            this.#buildTreeItem(
                module,
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
                throw Error(`Can't obtain ${name}. Might be missing in lookupPatterns.`);
            }

            return moduleFileMap[name];
        });

        return {
            files: standalonePathList.concat(modulePaths),
            depModules: allDepModules,
        };
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
     * @param {string} module
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
        module,
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
        let deps = map[module] || [];
        depth = depth || 0;
        path = [].concat(path || []);

        path.push(module);

        if (!(module in depthMap)) {
            depthMap[module] = depth;
        }
        else if (depth > depthMap[module]) {
            depthMap[module] = depth;
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
        for (let mod in this.modPaths) {
            let part = this.basePath + '/' + this.modPaths[mod] + '/src/';

            if (file.indexOf(part) === 0) {
                return `modules/${mod}/` + file.substring(part.length, file.length - 3);
            }
        }

        return file.slice(this.#getSrcPath().length, -3);
    }

    /**
     * @param {string} path
     * @return {{deps: string[], name: string}|null}
     */
    #obtainModuleData(path) {
        if (!this.#isClientJsFile(path)) {
            return null;
        }

        let moduleName = this.#obtainModuleName(path);

        const sourceCode = fs.readFileSync(path, 'utf-8');

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
            if (!sourceCode.includes('export ')) {
                return null;
            }

            if (!sourceCode.includes('import ')) {
                return {
                    name: moduleName,
                    deps: [],
                };
            }

            return {
                name: moduleName,
                deps: this.#obtainModuleDeps(tsSourceFile, moduleName),
            };
        }

        let deps = [];

        let argumentList = rootStatement.expression.arguments;

        for (let argument of argumentList.slice(0, 2)) {
            if (argument.elements) {
                argument.elements.forEach(node => {
                    if (!node.text) {
                        return;
                    }

                    let dep = this.#normalizeModModuleName(node.text);

                    deps.push(dep);
                });
            }
        }

        return {
            name: moduleName,
            deps: deps,
        };
    }

    /**
     * @param {string} sourceFile
     * @param {string} mod
     * @return {string[]}
     */
    #obtainModuleDeps(sourceFile, mod) {
        return sourceFile.statements
            .filter(item => item.importClause && item.moduleSpecifier)
            .map(item => item.moduleSpecifier.text)
            .map(/** string */item => {

                // @todo Normalize relative path.

                return this.#normalizeModModuleName(item);
            });
    }

    /**
     * @param {string} module
     * @return {string}
     */
    #normalizeModModuleName(module) {
        if (!module.includes(':')) {
            return module;
        }

        let [mod, part] = module.split(':');

        return `modules/${mod}/` + part;
    }

    /**
     * @param {string} path
     * @return {boolean}
     */
    #isClientJsFile(path) {
        if (path.slice(-3) !== '.js') {
            return false;
        }

        let startParts = [this.#getSrcPath()];

        for (let mod in this.modPaths) {
            let modPath = this.basePath + '/' + this.modPaths[mod] + '/src/';

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
        let srcPath = this.#getSrcPath();

        sourceCode = this.#stripSourceMappingUrl(sourceCode);

        if (!this.#isClientJsFile(path)) {
            return sourceCode;
        }

        if (!sourceCode.includes('define')) {
            return sourceCode;
        }

        let moduleName = path.slice(srcPath.length, -3);

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
     * @param {string} contents
     * @return {string}
     */
    #stripSourceMappingUrl(contents) {
        let re = /^\/\/# sourceMappingURL.*/gm;

        if (!contents.match(re)) {
            return contents;
        }

        return contents.replaceAll(re, '');
    }

    /**
     * @return {string}
     */
    #getSrcPath() {
        let path = this.srcPath;

        if (path.slice(-1) !== '/') {
            path += '/';
        }

        return path;
    }
}

module.exports = Bundler;
