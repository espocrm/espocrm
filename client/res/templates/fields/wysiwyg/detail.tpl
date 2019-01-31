{{#unless isPlain}}
    {{#if useIframe}}
    <iframe frameborder="0" style="width: 100%; overflow-x: hidden; overflow-y: hidden;" class="hidden"></iframe>
    {{else}}
    <div class="html-container">{{{value}}}</div>
    {{/if}}
{{else}}
<div class="plain complex-text hidden">{{complexText value}}</div>
{{/unless}}
{{#unless isNotEmpty}}{{#if valueIsSet}}{{translate 'None'}}{{/if}}{{/unless}}
