<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Label Manager' scope='Admin'}}</h3></div>

<div class="row">
    <div class="col-sm-3">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="cell">
                    <div class="field">
                        <select data-name="language" class="form-control">
                            {{#each languageList}}
                            <option
                                value="{{this}}"
                                {{#ifEqual this ../language}} selected{{/ifEqual}}
                            >{{translateOption this field='language'}}</option>
                            {{/each}}
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-body">
                <ul class="list-unstyled" style="overflow-x: hidden;">
                {{#each scopeList}}
                    <li>
                        <button
                            class="btn btn-link"
                            data-name="{{./this}}"
                            data-action="selectScope"
                        >{{translate this category='scopeNames'}}</button>
                    </li>
                {{/each}}
                </ul>
            </div>
        </div>
    </div>

    <div class="col-sm-9">
        <div class="language-record">
            {{{record}}}
        </div>
    </div>
</div>
