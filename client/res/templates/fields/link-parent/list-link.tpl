<a href="#{{scope}}/view/{{model.id}}" class="link" data-id="{{model.id}}" title="{{value}}">
{{#if idValue}}
{{#if value}}{{value}}{{else}}{{translate 'None'}}{{/if}}
{{else}}
    {{translate 'None'}}
{{/if}}
</a>