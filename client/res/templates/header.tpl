<div class="page-header-row">
    <div class="{{#if noBreakWords}} no-break-words{{/if}} page-header-column-1">
        <h3 class="header-title">{{{header}}}</h3>
    </div>
    <div class="page-header-column-2">
        <div class="header-buttons btn-group pull-right">
            {{#each items.buttons}}
                <a
                    {{#if link}}href="{{link}}"{{else}}href="javascript:"{{/if}}
                    class="btn btn-{{#if style}}{{style}}{{else}}default{{/if}} btn-xs-wide action{{#if hidden}} hidden{{/if}}"
                    data-name="{{name}}"
                    data-action="{{action}}"
                    {{#each data}} data-{{@key}}="{{./this}}"{{/each}}{{#if title}}
                    title="{{title}}"{{/if}}
                >
                {{#if iconHtml}}{{{iconHtml}}}{{/if}}
                {{#if html}}{{{html}}}{{else}}{{translate label scope=../scope}}{{/if}}
                </a>
            {{/each}}

            {{#if items.actions}}
                <div class="btn-group" role="group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    {{translate 'Actions'}} <span class="caret"></span>
                </button>
                <ul class="dropdown-menu pull-right">
                    {{#each items.actions}}
                    <li class="{{#if hidden}}hidden{{/if}}">
                        <a
                            {{#if link}}href="{{link}}"{{else}}href="javascript:"{{/if}}
                            class="action"
                            data-name="{{name}}"
                            data-action="{{action}}"
                            {{#each data}} data-{{@key}}="{{./this}}"{{/each}}
                        >{{#if html}}{{{html}}}{{else}}{{translate label scope=../scope}}{{/if}}</a></li>
                    {{/each}}
                </ul>
                </div>
            {{/if}}

            {{#if items.dropdown}}
                <div class="btn-group dropdown-group{{#unless hasVisibleDropdownItems}} hidden{{/unless}}" role="group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="fas fa-ellipsis-h"></span>
                </button>
                <ul class="dropdown-menu pull-right">
                    {{#each items.dropdown}}
                        {{#if this}}
                        <li class="{{#if hidden}}hidden{{/if}}">
                            <a
                                {{#if link}}href="{{link}}"{{else}}href="javascript:"{{/if}}
                                class="action"
                                data-name="{{name}}"
                                data-action="{{action}}"
                                {{#each data}} data-{{@key}}="{{./this}}"{{/each}}
                            >
                            {{#if iconHtml}}{{{iconHtml}}} {{/if}}
                            {{#if html}}{{{html}}}{{else}}{{translate label scope=../scope}}{{/if}}</a></li>
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
        </div>
    </div>
</div>
