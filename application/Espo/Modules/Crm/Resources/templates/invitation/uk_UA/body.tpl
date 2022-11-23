<p>Назва: {{name}}</p>
<p>Початок: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
    <a href="{{acceptLink}}" style="font-size: 1.2em">Прийняти</a> &middot;
    <a href="{{tentativeLink}}" style="font-size: 1.2em">Не впевнений</a> &middot;
    <a href="{{declineLink}}" style="font-size: 1.2em">Відхилити</a>
</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">Відкрити запис</a></p>
{{/if}}
