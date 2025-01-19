<p>{{assignerUserName}} ti ha assegnato {{entityTypeLowerFirst}}.</p>
<p><strong>{{name}}</strong></p>
<p>Inizio: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if parentName}}
<p>Genitore: {{parentName}}</p>
{{/if}}
{{#if description}}
<p>{{{description}}}</p>
{{/if}}
<p><a href="{{recordUrl}}">Vedi record</a></p>