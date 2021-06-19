<a href="#{{scope}}/view/{{model.id}}" class="link" data-id="{{model.id}}" title="{{value}}">
    {{#if value}}
        {{translateOption value scope=scope field=name translatedOptions=translatedOptions}}
    {{else}}
        {{translate 'None'}}
    {{/if}}
</a>
