<div class="no-side-margin">
    <table class="table table-bordered">
    {{#each dataList as |dataItem|}}
        <tr>
        {{#each dataItem}}
            <td>
                <a
                    role="button"
                    tabindex="0"
                    data-action="addField"
                    data-name="{{name}}"
                >{{label}}</a>
            </td>
        {{/each}}
        </tr>
    {{/each}}
    </table>
</div>
