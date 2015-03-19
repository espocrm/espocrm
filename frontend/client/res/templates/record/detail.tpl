<div class="detail" id="{{id}}">
    {{#if buttonsTop}}
    <div class="detail-button-container button-container record-buttons">
        {{#each buttonList}}{{button name scope=../../scope label=label style=style}}{{/each}}
    </div>
    {{/if}}
    <div class="detail-button-container button-container edit-buttons hidden">
        {{#each buttonEditList}}{{button name scope=../scope label=label style=style}}{{/each}}
    </div>

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
        {{#each buttonList}}
            {{button name scope=../../scope label=label style=style}}
        {{/each}}
    </div>
    {{/if}}
</div>
