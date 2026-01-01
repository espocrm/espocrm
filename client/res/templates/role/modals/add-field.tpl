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
<div class="list-container">
    <div class="list">
        <table class="table fields-table">
            {{#each dataList}}
                <tr data-name="{{name}}">
                    <td class="r-checkbox" style="width: 40px;">
                        <span class="record-checkbox-container">
                            <input
                                type="checkbox"
                                data-name="{{name}}"
                                class="record-checkbox form-checkbox form-checkbox-small"
                            >
                        </span>
                    </td>
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
</div>
