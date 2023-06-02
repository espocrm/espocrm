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

const Bundler = require("./bundler");
const Precompiler = require('./template-precompiler');

class BundlerGeneral {

    /**
     * @param {{
     *   chunks: Object.<string, {
     *     files?: string[],
     *     patterns?: string[],
     *     allPatterns?: string[],
     *     templatePatterns?: string[],
     *     noDuplicates?: boolean,
     *     dependentOn?: string[],
     *     libs?: string[],
     *   }>,
     *   modulePaths?: Record.<string, string>,
     *   allPatterns: string[],
     *   order: string[],
     * }} config
     * @param {{
     *    src?: string,
     *    bundle?: boolean,
     *    key?: string,
     *    files?: {
     *        src: string,
     *    }[]
     *  }[]} libs
     *  @param {string} [filePattern]
     */
    constructor(config, libs, filePattern) {
        this.config = config;
        this.libs = libs;
        this.mainBundleFiles = [];
        this.filePattern = filePattern || 'client/lib/espo-{*}.min.js';
    }

    /**
     * @return {Object.<string, string>}
     */
    bundle() {
        let result = {};
        let mapping = {};
        let files = [];
        let modules = [];
        let templateFiles = [];
        let mainName = this.config.order[0];

        /** @var {Object.<string, string[]>} */
        let notBundledMap = {};

        this.config.order.forEach((name, i) => {
            let data = this.#bundleChunk(name, i === 0, {
                files: files,
                templateFiles: templateFiles,
            });

            files = files.concat(data.files);
            templateFiles = templateFiles.concat(data.templateFiles);
            modules = modules.concat(data.modules);
            notBundledMap[name] = data.notBundledModules;
            result[name] = data.contents;

            if (i === 0) {
                return;
            }

            data.modules.forEach(item => mapping[item] = name);

            let bundleFile = this.filePattern.replace('{*}', name);

            let libs = this.config.chunks[name].libs;

            if (libs) {
                let part = JSON.stringify(libs.map(item => 'lib!' + item));

                result[mainName] += `Espo.loader.mapBundleDependencies('${name}', ${part});\n`;
            }

            result[mainName] += `Espo.loader.mapBundleFile('${name}', '${bundleFile}');\n`;
        });

        let notBundledModules = [];

        this.config.order.forEach(name => {
            notBundledMap[name]
                .filter(item => !modules.includes(item))
                .filter(item => !notBundledModules.includes(item))
                .forEach(item => notBundledModules.push(item));
        });

        if (notBundledModules.length) {
            let part = notBundledModules
                .map(item => ' ' + item)
                .join('\n');

            console.log(`\nNot bundled:\n${part}`);
        }

        result[mainName] += `Espo.loader.addBundleMapping(${JSON.stringify(mapping)});`

        return result;
    }

    /**
     * @param {string} name
     * @param {boolean} isMain
     * @param {{files: [], templateFiles: []}} alreadyBundled
     * @return {{
     *   contents: string,
     *   modules: string[],
     *   files: string[],
     *   templateFiles: string[],
     *   notBundledModules: string[],
     * }}
     */
    #bundleChunk(name, isMain, alreadyBundled) {
        let contents = '';

        let modules = [];

        let params = this.config.chunks[name];

        let patterns = params.patterns;
        let allPatterns = []
            .concat(this.config.allPatterns)
            .concat(params.allPatterns || []);

        let bundledFiles = [];
        let bundledTemplateFiles = [];
        let notBundledModules = [];

        if (params.patterns) {
            let bundler = (new Bundler(this.config.modulePaths));

            // The main bundle is always loaded, duplicates are not needed.
            let ignoreFiles = [].concat(this.mainBundleFiles);

            if (params.noDuplicates) {
                ignoreFiles = ignoreFiles.concat(alreadyBundled.files);
            }

            let data = bundler.bundle({
                files: params.files,
                patterns: patterns,
                allPatterns: allPatterns,
                libs: this.libs,
                ignoreFiles: ignoreFiles,
                dependentOn: params.dependentOn,
            });

            contents += data.contents;

            if (isMain) {
                this.mainBundleFiles = data.files;
            }

            modules = data.modules;
            bundledFiles = data.files;

            /*if (params.libs) {
                contents = this.#bundleLibs(params.libs) + '\n' + contents;
            }*/

            notBundledModules = data.notBundledModules;
        }

        if (params.templatePatterns) {
            let ignoreFiles = params.noDuplicates ? [].concat(alreadyBundled.templateFiles) : [];

            let data = (new Precompiler()).precompile({
                patterns: params.templatePatterns,
                modulePaths: this.config.modulePaths,
                ignoreFiles: ignoreFiles,
            });

            contents += '\n' + data.contents;
            bundledTemplateFiles = data.files;
        }

        return {
            contents: contents,
            modules: modules,
            files: bundledFiles,
            templateFiles: bundledTemplateFiles,
            notBundledModules: notBundledModules,
        };
    }

    /**
     * @param {string[]} libs
     * @return {string}
     */
    /*#bundleLibs(libs) {
        let files = [];

        this.libs
            .filter(item => libs.includes(item.key))
            .forEach(item => {
                if (item.src) {
                    files.push(item.src);

                    return;
                }

                if (!item.files) {
                    return;
                }

                item.files.forEach(item => {
                    files.push(item.src);
                });
            });

        let contents = files.map(file => fs.readFileSync(file, 'utf-8'));

        return contents.join('\n');
    }*/
}

module.exports = BundlerGeneral;
