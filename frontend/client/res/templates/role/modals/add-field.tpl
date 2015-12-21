<table class="table table-bordered">
{{#each dataList}}
    <tr>
    {{#each this}}
        <td>
            <a href="javascript:" data-action="addField" data-name="{{this}}">{{translate this scope=../../scope category='fields'}}</a>
        </td>
    {{/each}}
    </tr>
{{/each}}
</table>