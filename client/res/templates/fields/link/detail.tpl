{{#if idValue}}{{#if iconHtml}}{{{iconHtml}}}{{/if}}<a href="#{{foreignScope}}/view/{{idValue}}">{{nameValue}}</a>{{else}}
    {{#if valueIsSet}}{{translate 'None'}}{{else}}...{{/if}}
{{/if}}
