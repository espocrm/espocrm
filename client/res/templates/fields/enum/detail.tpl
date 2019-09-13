{{#if isNotEmpty}}{{#if style}}
<span class="{{class}}-{{style}}">{{/if}}{{translateOption value scope=scope field=name translatedOptions=translatedOptions}}{{#if style}}</span>{{/if}}
{{else}}
{{#if valueIsSet}}{{translate 'None'}}{{else}}...{{/if}}
{{/if}}