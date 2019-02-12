<h4>{{translate 'Step 1' scope='Import'}}</h4>

        <div class="panel panel-default">
            <div class="panel-heading"><h4 class="panel-title">{{translate 'What to Import?' scope='Import'}}</h4></div>
            <div class="panel-body panel-body-form">
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Entity Type' scope='Import'}}</label>
                        <select id="import-entity-type" class="form-control">
                            {{#each entityList}}
                            <option value="{{./this}}" {{#ifEqual this ../entityType}}selected{{/ifEqual}}>{{translate this category='scopeNamesPlural'}}</option>
                            {{/each}}
                        </select>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'File (CSV)' scope='Import'}}</label>
                        <div>
                            <input type="file" id="import-file">
                        </div>
                        <div class="text-muted small">{{translate 'utf8' category='messages' scope='Import'}}</div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'What to do?' scope='Import'}}</label>
                        <div>
                            <select class="form-control" id="import-action">
                                <option value="create">{{translate 'Create Only' scope='Import'}}</option>
                                <option value="createAndUpdate">{{translate 'Create and Update' scope='Import'}}</option>
                                <option value="update">{{translate 'Update Only' scope='Import'}}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Properties' scope='Import'}}</h4></div>
        <div class="panel-body panel-body-form">
            <div id="import-properties">
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Header Row' scope='Import'}}</label>
                        <div>
                            <input type="checkbox" id="import-header-row">
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Person Name Format' scope='Import'}}</label>
                        <div>
                            <select class="form-control" id="import-person-name-format">
                                <option value="f l">{{translate 'John Smith' scope='Import'}}</option>
                                <option value="l f">{{translate 'Smith John' scope='Import'}}</option>
                                <option value="l, f">{{translate 'Smith, John' scope='Import'}}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Field Delimiter' scope='Import'}}</label>
                        <select class="form-control" id="import-field-delimiter">
                            <option value=",">,</option>
                            <option value=";">;</option>
                            <option value="\t">\t</option>
                            <option value="|">|</option>
                        </select>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Date Format' scope='Import'}}</label>
                        <select class="form-control" id="import-date-format">
                            {{#each dateFormatDataList}}
                                <option value="{{key}}">{{value}}</option>
                            {{/each}}
                        </select>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Decimal Mark' scope='Import'}}</label>
                        <input class="form-control" type="text" id="import-decimal-mark" maxlength="1" value=".">
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Text Qualifier' scope='Import'}}</label>
                        <select class="form-control" id="import-text-qualifier">
                            <option value="&quot;">{{translate 'Double Quote' scope='Import'}}</option>
                            <option value="'">{{translate 'Single Quote' scope='Import'}}</option>
                        </select>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Time Format' scope='Import'}}</label>
                        <select class="form-control" id="import-time-format">
                            {{#each timeFormatDataList}}
                                <option value="{{key}}">{{value}}</option>
                            {{/each}}
                        </select>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Currency' scope='Import'}}</label>
                        <select class="form-control" id="import-currency">
                            {{#each currencyList}}
                            <option value="{{./this}}">{{./this}}</option>
                            {{/each}}
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Timezone' scope='Import'}}</label>
                        <select class="form-control" id="import-timezone">
                            {{#each timezoneList}}
                            <option value="{{./this}}">{{./this}}</option>
                            {{/each}}
                        </select>
                    </div>
                    <div class="col-sm-4 form-group cell">
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'inIdle' scope='Import' category='messages'}}</label>
                        <div>
                            <input type="checkbox" id="import-idle-mode">
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Skip searching for duplicates' scope='Import'}}</label>
                        <div>
                            <input type="checkbox" id="skip-duplicate-checking">
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Silent Mode' scope='Import'}}</label>
                        <div>
                            <input type="checkbox" id="import-silent-mode">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h4 class="panel-title">{{translate 'Preview' scope='Import'}}</h4></div>
        <div class="panel-body">
            <div id="import-preview" style="overflow-x: auto; overflow-y: hidden;">
            {{translate 'No Data'}}
            </div>
        </div>
    </div>

    <div style="padding-bottom: 10px;" class="clearfix">
        {{#if entityList.length}}
        <button class="btn btn-primary pull-right hidden" data-action="next">{{translate 'Next' scope='Import'}}</button>
        {{/if}}
    </div>

