<div class="detail" id="{{id}}">
    {{#unless buttonsDisabled}}
    <div class="detail-button-container button-container record-buttons clearfix">
        <div class="btn-group" role="group">
            {{#each buttonList}}{{button name scope=../../entityType label=label style=style hidden=hidden html=html}}{{/each}}
            {{#if dropdownItemList}}
            <button type="button" class="btn btn-default dropdown-toggle dropdown-item-list-button{{#if dropdownItemListEmpty}} hidden{{/if}}" data-toggle="dropdown">
                <span class="fas fa-ellipsis-h"></span>
            </button>
            <ul class="dropdown-menu pull-left">
                {{#each dropdownItemList}}
                {{#if this}}
                <li class="{{#if hidden}}hidden{{/if}}"><a href="javascript:" class="action" data-action="{{name}}">{{#if html}}{{{html}}}{{else}}{{translate label scope=../../../entityType}}{{/if}}</a></li>
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
        {{#if navigateButtonsEnabled}}
        <div class="pull-right">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-text btn-icon action {{#unless previousButtonEnabled}} disabled{{/unless}}" data-action="previous" title="{{translate 'Previous Entry'}}">
                    <span class="fas fa-chevron-left"></span>
                </button>
                <button type="button" class="btn btn-text btn-icon action {{#unless nextButtonEnabled}} disabled{{/unless}}" data-action="next" title="{{translate 'Next Entry'}}">
                    <span class="fas fa-chevron-right"></span>
                </button>
            </div>
        </div>
        {{/if}}
    </div>
    <div class="detail-button-container button-container edit-buttons hidden clearfix">
        <div class="btn-group" role="group">
        {{#each buttonEditList}}{{button name scope=../../entityType label=label style=style hidden=hidden html=html}}{{/each}}
        {{#if dropdownEditItemList}}
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="fas fa-ellipsis-h"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            {{#each dropdownEditItemList}}
            {{#if this}}
            <li class="{{#if hidden}}hidden{{/if}}"><a href="javascript:" class="action" data-action="{{name}}">{{#if html}}{{{html}}}{{else}}{{translate label scope=../../../entityType}}{{/if}}</a></li>
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
        <div class="left{{#if isWide}} col-md-12{{else}}{{#if isSmall}} col-md-7{{else}} col-md-8{{/if}}{{/if}}">
            <div class="middle">{{{middle}}}</div>
            <div class="extra">{{{extra}}}</div>
            <div class="bottom">{{{bottom}}}</div>
        </div>
        <div class="side{{#if isWide}} col-md-12{{else}}{{#if isSmall}} col-md-5{{else}} col-md-4{{/if}}{{/if}}">
        {{{side}}}
        </div>
    </div>
</div>
