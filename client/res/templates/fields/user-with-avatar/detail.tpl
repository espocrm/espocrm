{{#if idValue}}
{{{avatar}}}<a href="#{{foreignScope}}/view/{{idValue}}" class="{{#if linkClass}}{{linkClass}}{{/if}}">{{nameValue}}</a>
{{else}}
    <span class="none-value">{{translate 'None'}}</span>
{{/if}}
