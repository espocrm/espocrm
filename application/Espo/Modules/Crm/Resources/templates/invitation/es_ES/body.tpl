<p>Asunto: {{name}}</p>
<p>Comienzo: {{#if isAllDay}}{{dateStartFull}}{{else}}{{dateStartFull}} ({{timeZone}}){{/if}}</p>
{{#if isUser}}
    {{#if description}}
    <p>{{{description}}}</p>
    {{/if}}
{{/if}}
<p>
    <a href="{{acceptLink}}" style="font-size: 1.2em">Aceptar</a> &middot;
    <a href="{{tentativeLink}}" style="font-size: 1.2em">Provisional</a> &middot;
    <a href="{{declineLink}}" style="font-size: 1.2em">Declinar</a>
</p>
{{#if joinUrl}}
    <p>
        <a href="{{joinUrl}}">Unirse</a>
    </p>
{{/if}}
{{#if isUser}}
<p><a href="{{recordUrl}}">Ver registro</a></p>
{{/if}}
