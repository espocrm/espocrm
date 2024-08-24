<h4>{{translate 'Step 2' scope='Import'}}</h4>

    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Field Mapping' scope='Import'}}</h4></div>
        <div class="panel-body">
            <div id="mapping-container">
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Default Values' scope='Import'}}</h4></div>
        <div class="panel-body">
            <div class="button-container">
                <div class="btn-group">
                    <button class="btn btn-default dropdown-toggle add-field" data-toggle="dropdown">
                        {{translate 'Add Field' scope='Import'}}
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu pull-left default-field-list">
                        <li class="quick-search-list-item">
                            <input class="form-control add-field-quick-search-input">
                        </li>
                    {{#each fieldList}}
                        <li class="item" data-name="{{./this}}">
                            <a
                                role="button"
                                tabindex="0"
                                data-action="addField"
                                data-name="{{./this}}"
                            >{{translate this scope=../scope category='fields'}}</a></li>
                    {{/each}}
                    </ul>
                </div>
            </div>
            <div id="default-values-container" class="grid-auto-fill-md">
            </div>
        </div>
    </div>

    <div style="padding-bottom: 10px;" class="clearfix">
        <button
            class="btn btn-default btn-s-wide pull-left"
            data-action="back"
        >{{translate 'Back' scope='Import'}}</button>
        <button
            class="btn btn-danger btn-s-wide pull-right"
            data-action="next"
        >{{translate 'Run Import' scope='Import'}}</button>
    </div>
