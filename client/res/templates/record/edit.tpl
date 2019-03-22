<div class="edit" id="{{id}}">
    {{#unless buttonsDisabled}}
    <div class="detail-button-container button-container record-buttons clearfix">
        <div class="btn-group" role="group">
        {{#each buttonList}}{{button name scope=../../entityType label=label style=style html=html}}{{/each}}
        {{#if dropdownItemList}}
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="fas fa-ellipsis-h"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            {{#each dropdownItemList}}
            {{#if this}}
            <li><a href="javascript:" class="action" data-action="{{name}}">{{#if html}}{{{html}}}{{else}}{{translate label scope=../../../entityType}}{{/if}}</a></li>
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
        </div>
    </div>
    {{/unless}}

    <div class="row">
        <div class="left {{#if isWide}} col-md-12{{else}}{{#if isSmall}} col-md-7{{else}} col-md-8{{/if}}{{/if}}">
            <div class="middle">{{{middle}}}</div>
            <div class="extra">{{{extra}}}</div>
            <div class="bottom">{{{bottom}}}</div>
        </div>
        <div class="side{{#if isWide}} col-md-12{{else}}{{#if isSmall}} col-md-5{{else}} col-md-4{{/if}}{{/if}}">
        {{{side}}}
        </div>
    </div>
</div>
