{{#if isNotEmpty}}{{translateOption salutationValue field='salutationName' scope=scope}} {{firstValue}} {{lastValue}}{{else}}
{{#if valueIsSet}}{{{translate 'None'}}}{{else}}...{{/if}}
{{/if}}