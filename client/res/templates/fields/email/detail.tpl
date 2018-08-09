{{#if emailAddressData}}
    {{#each emailAddressData}}
        <div>
        {{#unless invalid}}
        {{#unless erased}}
        <a href="javascript:" data-email-address="{{emailAddress}}" data-action="mailTo">
        {{/unless}}
        {{/unless}}
        <span {{#if invalid}}style="text-decoration: line-through;"{{/if}}{{#if optOut}}style="text-decoration: line-through;"{{/if}}>{{emailAddress}}</span>
        {{#unless invalid}}
        {{#unless erased}}
        {{/unless}}
        </a>
        {{/unless}}
        </div>
    {{/each}}
{{else}}
    {{#if value}}
    <a href="javascript:" data-email-address="{{value}}" data-action="mailTo">{{value}}</a>
    {{else}}
        {{#if valueIsSet}}{{{translate 'None'}}}{{else}}...{{/if}}
    {{/if}}
{{/if}}
