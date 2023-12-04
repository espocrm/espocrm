{{#if list.length}}
    <select data-name="{{name}}" class="form-control main-element">
    {{#each list}}
        <option value="{{./this}}"{{#ifEqual ../value this}} selected{{/ifEqual}}>{{./this}}</option>
    {{/each}}
</select>
{{else}}
    {{{noSmtpMessage}}}
{{/if}}
