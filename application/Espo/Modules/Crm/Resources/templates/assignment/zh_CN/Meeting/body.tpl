<p>{{assignerUserName}}已分配{{entityTypeLowerFirst}}给你。</p>
<p><strong>{{name}}</strong></p>
<p>Start: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if parentName}}
<p>Parent: {{parentName}}</p>
{{/if}}
{{#if description}}
<p>{{{description}}}</p>
{{/if}}
<p><a href="{{recordUrl}}">View record</a></p>