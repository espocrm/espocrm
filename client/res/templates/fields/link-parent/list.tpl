{{#if idValue~}}
	{{#if iconHtml}}{{{iconHtml}}}{{/if~}}
        <a
            href="#{{foreignScope}}/view/{{idValue}}"
            title="{{nameValue}}"
            class="text-default"
        >{{nameValue}}</a>
{{~/if}}
