{{translate field category='fields' scope=scope}} {{{operatorString}}}
({{#each valueViewDataList}}<span data-name="{{key}}">{{{var key ../this}}}</span>{{#unless isEnd}}, {{/unless}}{{/each}})