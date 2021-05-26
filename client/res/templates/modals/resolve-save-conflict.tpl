<div class="margin-bottom-3x">
    <p>{{translate 'resolveSaveConflict' category='messages'}}</p>
</div>

<table class="table" style="table-layout: fixed;">
    <thead>
        <tr>
            <th width="25%">{{translate 'Field'}}</th>
            <th width="25%">{{translate 'Resolution'}}</th>
            <th>{{translate 'Value'}}</th>
        </tr>
    </thead>
    <tbody>
    {{#each dataList}}
        <tr>
            <td class="cell cell-nowrap">
                <span>
                    {{translate field category='fields' scope=../entityType}}
                </span>
            </td>
            <td class="cell">
                <select class="form-control" data-name="resolution" data-field="{{field}}">
                    {{options ../resolutionList resolution field='saveConflictResolution'}}
                </select>
            </td>
            <td class="cell">
                <div data-name="field" data-field="{{field}}">
                    {{{var viewKey ../this}}}
                </div>
            </td>
        </tr>
    {{/each}}
    </tbody>
</table>