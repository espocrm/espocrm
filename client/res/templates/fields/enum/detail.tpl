{{#if isNotEmpty}}
{{#if style}}
<span class="{{class}}-{{style}}"
>{{/if}}{{translateOption value scope=scope field=name translatedOptions=translatedOptions}}{{#if style}}</span>{{/if}}
{{else}}
{{#if valueIsSet}}
<span class="none-value">{{translate 'None'}}</span>
{{else}}
<span class="loading-value">...</span>
{{/if}}
{{/if}}
