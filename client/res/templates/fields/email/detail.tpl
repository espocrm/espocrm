{{#if emailAddressData}}
    {{#each emailAddressData}}
        <div>
            {{#unless invalid}}
            {{#unless erased}}
            <a
                role="button"
                tabindex="0"
                data-email-address="{{emailAddress}}"
                data-action="mailTo"
                class="selectable"
            >
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
    <a
        role="button"
        tabindex="0"
        data-email-address="{{value}}"
        data-action="mailTo"
        class="selectable"
    >{{value}}</a>
    {{else}}
        {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
        <span class="loading-value"></span>{{/if}}
    {{/if}}
{{/if}}
