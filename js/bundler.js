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

const typescript = require('typescript');
const fs = require('fs');

/**
 * Normalizes and concatenates Espo modules.
 */
class Bundler {

    /**
     * @private
     * @type {string}
     */
    basePath = 'client/src'

    /**
     * @param {string[]} pathList
     * @return {string}
     */
    bundle(pathList) {
        let bundleContents = '';

        pathList.forEach(path => {
            bundleContents += this.normalizeSourceFile(path);
        })

        return bundleContents;
    }

    /**
     * @private
     * @param {string} path
     * @return {string}
     */
    normalizeSourceFile(path) {
        let sourceCode = fs.readFileSync(path, 'utf-8');
        let basePath = this._getBathPath();

        if (
            path.indexOf(basePath) !== 0 ||
            path.slice(-3) !== '.js'
        ) {
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
