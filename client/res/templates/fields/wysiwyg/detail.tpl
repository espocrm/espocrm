{{#unless isPlain}}
    {{#if useIframe}}
    <iframe frameborder="0"  style="width: 100%;" class="hidden" scrolling="no"></iframe>
    {{else}}
    <div class="html-container">{{{value}}}</div>
    {{/if}}
{{else}}
<div class="plain complex-text hidden">{{complexText value}}</div>
{{/unless}}
