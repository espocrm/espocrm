{{#if idValue}}{{#if iconHtml}}{{{iconHtml}}}{{/if}}<a href="#{{foreignScope}}/view/{{idValue}}" title="{{translate foreignScope category='scopeNames'}}">{{nameValue}}</a>{{else}}
    {{#if valueIsSet}}{{#if displayEntityType}}{{translate typeValue category='scopeNames'}}{{else}}{{translate 'None'}}{{/if}}{{else}}...{{/if}}
{{/if}}
