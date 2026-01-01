{{#if isEmpty}}
    {{translate 'None'}}
{{else}}
    <div>(
    {{#each viewDataList}}
        <div data-view-key="{{key}}" style="margin-left: 15px;">{{{var key ../this}}}</div>
        {{#unless isEnd}}
        <div style="margin-left: 15px;">
            {{translate ../operator category='logicalOperators' scope='Admin'}}
        </div>
        {{/unless}}
    {{/each}}
    )</div>
{{/if}}