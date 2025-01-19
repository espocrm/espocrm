<p>Betreff: {{name}}</p>
<p>Start: {{#if isAllDay}}{{dateStartFull}}{{else}}{{dateStartFull}} ({{timeZone}}){{/if}}</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">Eintrag öffnen</a></p>
{{/if}}
