<p>{{assignerUserName}} hat Ihnen {{entityType}} zugewiesen.</p>
<p><strong>{{name}}</strong></p>
<p>Beginn: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if parentName}}
<p>Bezieht sich auf: {{parentName}}</p>
{{/if}}
{{#if description}}
<p>{{{description}}}</p>
{{/if}}
<p><a href="{{recordUrl}}">Eintrag öffnen</a></p>