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
const Handlebars = require('handlebars');

class TemplatePrecompiler {

    defaultPath = 'client';

    /**
     * @param {{
     *   patterns: string[],
     *   modulePaths: Object.<string, string>,
     * }} params
     * @return {string}
     */
    precompile(params) {
        let files = [];

        params.patterns.forEach(pattern => {
            let itemFiles = globSync(pattern)
                .map(file => file.replaceAll('\\', '/'));

            files = files.concat(itemFiles);
        });

        let nameMap = {};

        files.forEach(file => {
            let module = null;

            for (let itemModule in params.modulePaths) {
                let path = params.modulePaths[itemModule];

                if (file.indexOf(path) === 0) {
                    module = itemModule;

                    break;
                }
            }

            let path = module ?
                params.modulePaths[module] :
                this.defaultPath;

            path += '/res/templates/';

            let name = file.substring(path.length).slice(0, -4);

            if (module) {
                name = module + ':' + name;
            }

            nameMap[file] = name
        });

        let contents =
            'Espo.preCompiledTemplates = Espo.preCompiledTemplates || {};\n' +
            'Object.assign(Espo.preCompiledTemplates, {\n';

        for (let file in nameMap) {
            let name = nameMap[file];

            let templateContent = fs.readFileSync(file, 'utf8');
            let compiled = Handlebars.precompile(templateContent);

            contents += `'${name}': Handlebars.template(\n${compiled}\n),\n`;
        }

        contents += `\n});`;

        return contents;
    }
}

module.exports = TemplatePrecompiler;
