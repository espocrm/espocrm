<div class="edit" id="{{id}}">
    {{#if buttonsTop}}
        <div class="detail-button-container button-container record-buttons">
            {{#each buttonList}}{{button name scope=../../scope label=label style=style}}{{/each}}
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
        <div class="button-container record-buttons">
            {{#each buttonList}}
                {{button name scope=../../scope label=label style=style}}
            {{/each}}
        </div>
    {{/if}}
</div>


