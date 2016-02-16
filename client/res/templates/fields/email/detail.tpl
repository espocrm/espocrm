{{#if emailAddressData}}
    {{#each emailAddressData}}
        <div>
        {{#unless invalid}}{{#unless optOut}}
        <a href="javascript:" data-email-address="{{emailAddress}}" data-action="mailTo">
        {{/unless}}{{/unless}}

        <span {{#if invalid}}style="text-decoration: line-through;"{{/if}}>
        {{emailAddress}}
        </span>

        {{#unless invalid}}{{#unless optOut}}
        </a>
        {{/unless}}{{/unless}}
        </div>
    {{/each}}
{{else}}
    {{#if value}}
    <a href="javascript:" data-email-address="{{value}}" data-action="mailTo">{{value}}</a>
    {{else}}
        {{translate 'None'}}
    {{/if}}
{{/if}}
