{{#if list.length}}
    <select data-name="{{name}}" class="form-control main-element">
    {{#each list}}
        <option value="{{./this}}">{{./this}}</optopn>
    {{/each}}
</select>
{{else}}
    {{{noSmtpMessage}}}
{{/if}}
