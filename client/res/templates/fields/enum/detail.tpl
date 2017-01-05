{{#if isNotEmpty}}
{{translateOption value scope=scope field=name translatedOptions=translatedOptions}}
{{else}}
{{translate 'None'}}
{{/if}}