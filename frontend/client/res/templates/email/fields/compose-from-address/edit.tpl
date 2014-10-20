{{#if list.length}}
    <select name="{{name}}" class="form-control main-element">
    {{#each list}}
        <option value="{{./this}}">{{./this}}</optopn>
    {{/each}}    
</select>
{{else}}
    {{{noSmtpMessage}}}    
{{/if}}
