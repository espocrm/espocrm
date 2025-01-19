<p>Oggetto: {{name}}</p>
<p>Inizio: {{#if isAllDay}}{{dateStartFull}}{{else}}{{dateStartFull}} ({{timeZone}}){{/if}}</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">Vedi record</a></p>
{{/if}}
