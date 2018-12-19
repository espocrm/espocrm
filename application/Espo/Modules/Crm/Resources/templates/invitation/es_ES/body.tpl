<p>Asunto: {{name}}</p>
<p>Comienzo: {{#if isAllDay}}{{dateStartDate}}{{else}}{{dateStart}}{{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
<a href="{{acceptLink}}">Aceptar</a>, <a href="{{declineLink}}">Declinar</a>, <a href="{{tentativeLink}}">Provisional</a>
</p>
{{#if isUser}}
<p><a href="{{recordUrl}}">Ver registro</a></p>
{{/if}}
