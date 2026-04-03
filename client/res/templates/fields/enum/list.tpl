{{#if isNotEmpty~}}
    {{~#if hasColor~}}
        <span
            class="color-icon fas fa-square text-soft"
            style=" {{#if color}} color: {{color}}; {{/if}} "
        ></span><span style="user-select: none">&nbsp;</span>
    {{~/if~}}
    {{~#if style~}}
        <span
            class="{{class}}-{{style}}"
            title="{{valueTranslated}}"
        >
    {{~/if~}}
    {{valueTranslated}}
    {{~#if style}}</span>{{~/if~}}
{{~/if~}}
