<div class="edit" id="{{id}}">
    {{#if buttonsTop}}
    <div class="detail-button-container button-container record-buttons">
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
        {{/if}}
        </div>
    </div>
    {{/if}}


    <div class="row">
        {{#if isWide}}
        <div class="col-md-12">
        {{else}}
        <div class="{{#unless isSmall}} col-md-8{{else}} col-md-7{{/unless}}">
        {{/if}}
            <div class="record">{{{record}}}</div>
            <div class="extra">{{{extra}}}</div>
            <div class="bottom">{{{bottom}}}</div>
        </div>
        <div class="side{{#unless isSmall}} col-md-4{{else}} col-md-5{{/unless}}">
        {{{side}}}
        </div>
    </div>


    {{#if buttonsBottom}}
    <div class="detail-button-container button-container record-buttons">
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
        {{/if}}
        </div>
    </div>
    {{/if}}
</div>


