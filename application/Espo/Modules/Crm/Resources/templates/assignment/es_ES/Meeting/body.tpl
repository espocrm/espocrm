<p>{{assignerUserName}} te ha asignado {{entityTypeLowerFirst}} a ti.</p>
<p><strong>{{name}}</strong></p>
<p>Comienzo: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if parentName}}
<p>Padre: {{parentName}}</p>
{{/if}}
{{#if description}}
<p>{{{description}}}</p>
{{/if}}
<p><a href="{{recordUrl}}">Ver registro</a></p>
