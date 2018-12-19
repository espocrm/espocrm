<p>Назва: {{name}}</p>
<p>Початок: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
<a href="{{acceptLink}}">Прийняти</a>, <a href="{{declineLink}}">Відхилити</a>, <a href="{{tentativeLink}}">Не впевнений</a>
</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">Відкрити запис</a></p>
{{/if}}