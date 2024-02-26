
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

<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'Scope Level' scope='Role'}}</h4>
    </div>
    <div class="panel-body">
        <div class="no-margin">
            <table class="table table-bordered-inside no-margin scope-level">
                <tr>
                    <th></th>
                    <th style="width: 20%">{{translate 'Access' scope='Role'}}</th>
                    {{#each actionList}}
                        <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                    {{/each}}
                </tr>
                {{#each tableDataList}}
                    {{#unless this}}
                        <tr data-name="_" class="item-row">
                            <td>&#8203;</td><td></td>
                        </tr>
                    {{else}}
                        <tr data-name="{{name}}" class="item-row">
                            <td><b>{{translate name category='scopeNamesPlural'}}</b></td>

                            <td>
                                <span class="text-{{prop ../styleMap access}}"
                                >{{translateOption access scope='Role' field='accessList'}}</span>
                            </td>

                            {{#ifNotEqual type 'boolean'}}
                                {{#each list}}
                                    <td>
                                        {{#ifNotEqual access 'not-set'}}
                                            <span
                                                class="text-{{prop ../../styleMap level}}"
                                                title="{{translate action scope='Role' category='actions'}}"
                                            >{{translateOption level field='levelList' scope='Role'}}</span>
                                        {{/ifNotEqual}}
                                    </td>
                                {{/each}}
                            {{/ifNotEqual}}
                        </tr>
                    {{/unless}}
                {{/each}}
            </table>

            <div class="sticky-header-scope hidden sticky-head">
                <table class="table borderless no-margin">
                    <tr>
                        <th></th>
                        <th style="width: 20%">{{translate 'Access' scope='Role'}}</th>
                        {{#each actionList}}
                            <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                        {{/each}}
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>


{{#if hasFieldLevelData}}
<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'Field Level' scope='Role'}}</h4>
    </div>
    <div class="panel-body">
        <div class="no-margin">
            <table class="table table-bordered-inside no-margin field-level">
                <tr>
                    <th></th>
                    <th style="width: 20%"></th>
                    {{#each fieldActionList}}
                        <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                    {{/each}}
                    <th style="width: 33%"></th>
                </tr>
                {{#each fieldTableDataList}}
                    {{#if list.length}}
                    <tr data-name="{{name}}" class="item-row accented">
                        <td><b>{{translate name category='scopeNamesPlural'}}</b></td>
                        <td></td>
                        <td colspan="3"></td>
                    </tr>
                    {{/if}}
                    {{#each list}}
                    <tr data-name="{{../name}}" class="item-row">
                        <td></td>
                        <td>{{translate name category='fields' scope=../name}}</td>
                        {{#each list}}
                        <td>
                            <span
                                title="{{translate name scope='Role' category='actions'}}"
                                class="text-{{prop ../../../styleMap value}}"
                            >{{translateOption value scope='Role' field='levelList'}}</span>
                        </td>
                        {{/each}}
                        <td colspan="3"></td>
                    </tr>
                    {{/each}}
                {{/each}}
            </table>

            <div class="sticky-header-field hidden sticky-head">
                <table class="table borderless no-margin">
                    <tr>
                        <th></th>
                        <th style="width: 20%"></th>
                        {{#each fieldActionList}}
                            <th style="width: 11%">{{translate this scope='Role' category='actions'}}</th>
                        {{/each}}
                        <th style="width: 33%"></th>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
{{/if}}
