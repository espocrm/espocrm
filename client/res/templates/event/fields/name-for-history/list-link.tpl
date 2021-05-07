<a
    href="#{{model.name}}/view/{{model.id}}"
    class="link"
    data-id="{{model.id}}"
    title="{{value}}"
    {{#if strikethrough}}style="text-decoration: line-through;"{{/if}}
>{{#if value}}{{value}}{{else}}{{translate 'None'}}{{/if}}</a>
