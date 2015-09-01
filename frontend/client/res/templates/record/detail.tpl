<div class="detail" id="{{id}}">
    {{#if buttonsTop}}
    <div class="detail-button-container button-container record-buttons">
        <div class="btn-group" role="group">
        {{#each buttonList}}{{button name scope=../../scope label=label style=style hidden=hidden}}{{/each}}
        {{#if dropdownItemList}}
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            {{#each dropdownItemList}}
            <li class="{{#if hidden}}hidden{{/if}}"><a href="javascript:" class="action" data-action="{{name}}">{{translate label scope=../scope}}</a></li>
            {{/each}}
        </ul>
        {{/if}}
        </div>
    </div>
    <div class="detail-button-container button-container edit-buttons hidden">
        <div class="btn-group" role="group">
        {{#each buttonEditList}}{{button name scope=../../scope label=label style=style hidden=hidden}}{{/each}}
        {{#if dropdownEditItemList}}
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            {{#each dropdownEditItemList}}
            <li class="{{#if hidden}}hidden{{/if}}"><a href="javascript:" class="action" data-action="{{name}}">{{translate label scope=../scope}}</a></li>
            {{/each}}
        </ul>
        {{/if}}
        </div>
    </div>
    {{/if}}


    <div class="row">
        <div class="{{#if isWide}} col-md-12{{else}}{{#if isSmall}} col-md-7{{else}} col-md-8{{/if}}{{/if}}">
            <div class="record">{{{record}}}</div>
            <div class="extra">{{{extra}}}</div>
            <div class="bottom">{{{bottom}}}</div>
        </div>
        <div class="side {{#if isSmall}} col-md-5{{else}} col-md-4{{/if}}">
        {{{side}}}
        </div>
    </div>

    {{#if buttonsBottom}}
    <div class="button-container record-buttons">
        <div class="btn-group" role="group">
        {{#each buttonList}}{{button name scope=../../scope label=label style=style}}{{/each}}
        {{#if dropdownItemList}}
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            {{#each dropdownItemList}}
            <li><a href="javascript:" class="action" data-action="{{name}}">{{translate label scope=../scope}}</a></li>
            {{/each}}
        </ul>
        </div>
        {{/if}}
        </div>
    </div>
    <div class="detail-button-container button-container edit-buttons hidden">
        <div class="btn-group" role="group">
        {{#each buttonEditList}}{{button name scope=../../scope label=label style=style}}{{/each}}
        {{#if dropdownEditItemList}}
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-left">
            {{#each dropdownEditItemList}}
            <li><a href="javascript:" class="action" data-action="{{name}}">{{translate label scope=../scope}}</a></li>
            {{/each}}
        </ul>
        {{/if}}
        </div>
    </div>
    {{/if}}
</div>
