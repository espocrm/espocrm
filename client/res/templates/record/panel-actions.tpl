{{#each buttonList}}
<button type="button" class="btn btn-{{#if ../defs.style}}{{../defs.style}}{{else}}default{{/if}} btn-sm action{{#if hidden}} hidden{{/if}}" data-action="{{action}}" data-panel="{{../defs.name}}" {{#each data}} data-{{hyphen @key}}="{{./this}}"{{/each}} title="{{#if title}}{{translate title scope=../../scope}}{{/if}}">{{#if html}}{{{html}}}{{else}}{{translate label scope=../../../scope}}{{/if}}</button>
{{/each}}

{{#if actionList}}
    <button type="button" class="btn btn-{{#if defs.style}}{{defs.style}}{{else}}default{{/if}} btn-sm dropdown-toggle" data-toggle="dropdown">
        <span class="fas fa-ellipsis-h"></span>
    </button>
    <ul class="dropdown-menu">
        {{#each actionList}}
        {{#if this}}
        <li><a {{#if link}}href="{{link}}"{{else}}href="javascript:"{{/if}} class="action{{#if hidden}} hidden{{/if}}" {{#if action}} data-panel="{{../../../defs.name}}" data-action="{{action}}"{{/if}}{{#each data}} data-{{hyphen @key}}="{{./this}}"{{/each}}>{{#if html}}{{{html}}}{{else}}{{translate label scope=../../../scope}}{{/if}}</a></li>
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
