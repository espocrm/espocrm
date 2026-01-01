<a href="#{{scope}}/view/{{model.id}}" class="link" data-id="{{model.id}}" title="{{value}}">
    {{#if value}}
        {{#if style}}
        <span
            class="{{class}}-{{style}}"
            title="{{valueTranslated}}"
        >{{/if}}{{valueTranslated}}{{#if style}}</span>{{/if}}
    {{else}}
        {{translate 'None'}}
    {{/if}}
</a>
