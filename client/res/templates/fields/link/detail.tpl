{{#if idValue}}
<a href="#{{foreignScope}}/view/{{idValue}}">{{nameValue}}</a>
{{else}}
    {{#if valueIsSet}}{{translate 'None'}}{{else}}...{{/if}}
{{/if}}
