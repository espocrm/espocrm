{{#if url}}
{{#if iconHtml}}{{{iconHtml}}}{{/if}}<a href="{{url}}" class="{{#if linkClass}}{{linkClass}}{{/if}}">{{nameValue}}</a>
{{else}}
    {{#if valueIsSet}}
    <span class="none-value">{{translate 'None'}}</span>
    {{else}}
    <span class="loading-value"></span>
    {{/if}}
{{/if}}
