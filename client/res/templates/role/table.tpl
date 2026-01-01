
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

                            <td data-name="{{name}}">{{{lookup ../this name}}}</td>

                            {{#ifNotEqual type 'boolean'}}
                                {{#each list}}
                                    <td data-name="{{name}}">
                                        <div
                                            data-name="{{name}}"
                                            class="cell {{#if (lookup ../../hiddenFields name) }} hidden  {{/if}} "
                                        >
                                            {{#ifNotEqual access 'not-set'}}
                                                {{{lookup ../../this name}}}
                                            {{/ifNotEqual}}
                                        </div>
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
                            <div data-name="{{name}}">{{{lookup ../../../this name}}}</div>
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
