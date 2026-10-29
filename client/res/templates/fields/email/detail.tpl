{{#if emailAddressData}}
    {{#each emailAddressData}}
        <div>
            {{~#if invalid~}}
                <span
                    class="fas fa-exclamation-circle fa-sm text-muted"
                    title="{{translate 'Invalid' scope='EmailAddress'}}"
                ></span>
                {{~null~}}<span style="user-select: none">&nbsp;</span>{{~null~}}
            {{/if~}}
            {{~#unless invalid}}
            {{~#unless erased}}
            <a
                role="button"
                tabindex="0"
                data-email-address="{{emailAddress}}"
                data-action="mailTo"
                class="selectable"
            >
            {{~/unless~}}
            {{~/unless~}}
            <span
                class="{{#if optOut}} text-strikethrough {{/if}}"
            >{{emailAddress}}</span>
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
