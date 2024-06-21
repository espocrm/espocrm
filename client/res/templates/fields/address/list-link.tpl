{{#if formattedAddress}}
    <a
        href="#{{scope}}/view/{{model.id}}"
        class="link"
        data-id="{{model.id}}"
    >{{breaklines formattedAddress}}</a>
{{/if}}
