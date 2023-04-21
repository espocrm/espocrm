<h4>{{translate 'duplicate' category="messages"}}</h4>

<div style="margin-top: 20px;">
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
</div>
