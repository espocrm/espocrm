{{#if idValue}}{{#if iconHtml}}{{{iconHtml}}}{{/if}}<a href="#{{foreignScope}}/view/{{idValue}}" title="{{translate foreignScope category='scopeNames'}}">{{nameValue}}</a>{{else}}
    {{#if valueIsSet}}{{translate 'None'}}{{else}}...{{/if}}
{{/if}}
