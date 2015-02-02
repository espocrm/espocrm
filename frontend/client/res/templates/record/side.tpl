{{#each panels}}
<div class="panel panel-default panel-{{name}}">
    {{#if label}}
    <div class="panel-heading">
        <div class="pull-right btn-group">
            {{#if actions}}
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    {{#each actions}}
                    <li><a {{#if link}}href="{{link}}"{{else}}href="javascript:"{{/if}} class="action" {{#if action}} data-panel="{{../../name}}" data-action="{{action}}"{{/if}}{{#each data}} data-{{@key}}="{{./this}}"{{/each}}>{{translate label scope=../scope}}</a></li>
                    {{/each}}
                </ul>
            {{/if}}
        </div>
        <h4 class="panel-title">
            {{#unless notRefreshable}}
            <span style="cursor: pointer;" class="action" title="{{translate 'clickToRefresh' category='messages'}}" data-action="refresh" data-panel="{{name}}">
            {{/unless}}
            {{title}}
            {{#unless notRefreshable}}
            </span>
            {{/unless}}
        </h4>
    </div>
    {{/if}}
    <div class="panel-body panel-body-{{name}}">
        {{{var name ../this}}}
    </div>
</div>
{{/each}}
