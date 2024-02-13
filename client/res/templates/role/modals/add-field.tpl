<div class="button-container negate-no-side-margin">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>
<div class="no-side-margin">
    <table class="table table-bottom-bordered fields-table">
    {{#each dataList}}
        <tr data-name="{{name}}">
            <td>
                <a
                    role="button"
                    tabindex="0"
                    data-action="addField"
                    data-name="{{name}}"
                >{{label}}</a>
            </td>
        </tr>
    {{/each}}
    </table>
</div>
