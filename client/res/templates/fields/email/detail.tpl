{{#if emailAddressData}}
    {{#each emailAddressData}}
        <div>
            {{#unless invalid}}
            {{#unless erased}}
            <a href="javascript:" data-email-address="{{emailAddress}}" data-action="mailTo">
            {{/unless}}
            {{/unless}}
            <span {{#if lineThrough}}style="text-decoration: line-through"{{/if}}>{{emailAddress}}</span>
            {{#unless invalid}}
            {{#unless erased}}
            </a>
            {{/unless}}
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
