{{#if actionList.length}}
<div class="list-row-buttons btn-group pull-right">
    <button
        type="button"
        class="btn btn-link btn-sm dropdown-toggle"
        data-toggle="dropdown"
    ><span class="caret"></span></button>
    <ul class="dropdown-menu pull-right list-row-dropdown-menu" data-id="{{model.id}}">
    {{#each actionList}}
        {{#if this}}
            <li
                {{#if viewKey}} data-view-key="{{viewKey}}" {{/if}}
            >
                {{#if viewKey}}
                    {{{lookup ../this viewKey}}}
                {{else}}
                    <a
                        {{#if link}} href="{{link}}" {{else}} role="button" {{/if}}
                            tabindex="0"
                            class="action"
                            {{#if action}}data-action="{{action}}"{{/if}}
                        {{#each data}}
                            data-{{hyphen @key}}="{{./this}}"
                        {{/each}}
                    >{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label scope=../scope}}{{/if}}{{/if}}
                    </a>
                {{/if}}
            </li>
        {{else}}
            {{#unless @first}}
                {{#unless @last}}
                    <li class="divider"></li>
                {{/unless}}
            {{/unless}}
        {{/if}}
    {{/each}}
    </ul>
</div>
{{/if}}
