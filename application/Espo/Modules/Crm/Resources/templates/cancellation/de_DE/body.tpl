<p>Betreff: {{name}}</p>
<p>Start: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">Eintrag öffnen</a></p>
{{/if}}
