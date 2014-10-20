<div class="detail" id="{{id}}">
    {{#if buttonsTop}}
    <div class="detail-button-container button-container record-buttons">
        {{#each buttons}}
            {{button name scope=../../scope label=label style=style}}
        {{/each}}
    </div>
    {{/if}}
    <div class="detail-button-container button-container edit-buttons hidden">
        {{#each buttonsEdit}}
            {{button name scope=../scope label=label style=style}}
        {{/each}}
    </div>

    <div class="row">
        <div class="{{#if isWide}} col-md-12{{else}} col-md-8{{/if}}">
            <div class="record">{{{record}}}</div>
            <div class="extra">{{{extra}}}</div>
            <div class="bottom">{{{bottom}}}</div>
        </div>
        <div class="side col-md-4">
        {{{side}}}
        </div>
    </div>

    {{#if buttonsBottom}}
    <div class="button-container record-buttons">
        {{#each buttons}}
            {{button name scope=../../scope label=label style=style}}
        {{/each}}
    </div>
    {{/if}}
</div>
