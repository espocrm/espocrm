{{#if isNotEmpty}}
    {{~#if hasColor~}}
        <span
            class="color-icon fas fa-square text-soft"
            style=" {{#if color}} color: {{color}}; {{/if}} "
        ></span><span style="user-select: none">&nbsp;</span>
    {{~/if~}}
    {{~#if style~}}
        <span class="{{class}}-{{style}}">
    {{~/if~}}
        {{valueTranslated}}
    {{~#if style~}}</span>{{~/if~}}
{{else}}
    {{~#if valueIsSet~}}
        <span class="none-value">{{translate 'None'}}</span>
    {{~else~}}
        <span class="loading-value"></span>
    {{~/if~}}
{{/if~}}
