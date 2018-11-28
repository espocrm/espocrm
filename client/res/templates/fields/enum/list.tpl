{{#if isNotEmpty}}{{#if style}}
<span class="text-{{style}}">{{/if}}{{translateOption value scope=scope field=name translatedOptions=translatedOptions}}{{#if style}}</span>{{/if}}
{{/if}}