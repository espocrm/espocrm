<h4>{{translate 'duplicate' category="messages"}}</h4>

{{#if scope}}
<div class="list-container margin-top-2x">{{{record}}}</div>
{{else}}
<div class="margin-top-2x">
    <table class="table table-panel">
        {{#each duplicates}}
        <tr>
            <td>
                <a
                    href="#{{#if _entityType}}{{_entityType}}{{else}}{{../scope}}{{/if}}/view/{{id}}"
                    target="_BLANK"
                >{{name}}</a>
                {{#if _entityType}}({{translate _entityType category='scopeNames'}}){{/if}}
            </td>
        </tr>
        {{/each}}
    </table>
    {{/if}}
</div>
