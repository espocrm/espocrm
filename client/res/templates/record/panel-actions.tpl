{{#each buttonList}}
    <button
        type="button"
        class="btn btn-{{#if ../defs.style}}{{../defs.style}}{{else}}default{{/if}} btn-sm panel-action action{{#if hidden}} hidden{{/if}}"
        {{#if action}}data-action="{{action}}"{{/if}}
        {{#if name}}data-name="{{name}}"{{/if}}
        data-panel="{{../defs.name}}" {{#each data}} data-{{hyphen @key}}="{{./this}}"{{/each}}
        title="{{#if title}}{{translate title scope=../scope}}{{/if}}"
    >{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label scope=../scope}}{{/if}}{{/if}}</button>
{{/each}}

{{#if actionList}}
    <button
        type="button"
        class="btn btn-{{#if defs.style}}{{defs.style}}{{else}}default{{/if}} btn-sm dropdown-toggle"
        data-toggle="dropdown"
    ><span class="fas fa-ellipsis-h"></span></button>
    <ul class="dropdown-menu">
        {{#each actionList}}
            {{#if this}}
                {{dropdownItem
                    action
                    scope=../scope
                    label=label
                    labelTranslation=labelTranslation
                    html=html
                    title=title
                    text=text
                    hidden=hidden
                    disabled=disabled
                    data=data
                    link=link
                    className='panel-action'
                }}
            {{else}}
                {{#unless @first}}
                    {{#unless @last}}
                        <li class="divider"></li>
                    {{/unless}}
                {{/unless}}
            {{/if}}
        {{/each}}
    </ul>
{{/if}}
