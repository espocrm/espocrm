{{#if value}}
    {{{value}}}
{{else}}
    {{#if valueIsSet}}
        <span class="none-value">{{translate 'None'}}</span>
    {{else}}
        ...
    {{/if}}
{{/if}}