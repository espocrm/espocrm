{{#if idValue~}}
	{{#if iconHtml}}{{{iconHtml}}}{{/if~}}
        <a
            href="#{{foreignScope}}/view/{{idValue}}"
            title="{{nameValue}}"
            class="{{#if linkClass}} {{linkClass}} {{/if}}"
        >{{nameValue}}</a>
{{~/if}}
