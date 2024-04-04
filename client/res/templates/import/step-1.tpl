<h4>{{translate 'Step 1' scope='Import'}}</h4>

        <div class="panel panel-default">
            <div class="panel-heading"><h4 class="panel-title">{{translate 'What to Import?' scope='Import'}}</h4></div>
            <div class="panel-body panel-body-form">
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Entity Type' scope='Import'}}</label>
                        <div data-name="entityType" class="field">
                            {{{entityTypeField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'File (CSV)' scope='Import'}}</label>
                        <div>
                            <label class="attach-file-label">
                                <span class="btn btn-default btn-icon">
                                    <span class="fas fa-paperclip"></span>
                                </span>
                                <input type="file" id="import-file" accept=".csv" class="file">
                            </label>
                            <div class="import-file-name"></div>
                        </div>
                        <div class="text-muted import-file-info">{{translate 'utf8' category='messages' scope='Import'}}</div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'What to do?' scope='Import'}}</label>
                        <div data-name="action" class="field">
                            {{{actionField}}}
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
                        <div data-name="headerRow" class="field">
                            {{{headerRowField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Person Name Format' scope='Import'}}</label>
                        <div data-name="personNameFormat" class="field">
                            {{{personNameFormatField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <div class="pull-right">
                            <button
                                class="btn btn-link hidden"
                                data-action="saveAsDefault"
                            >{{translate 'saveAsDefault' category='strings' scope='Import'}}</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Field Delimiter' scope='Import'}}</label>
                        <div data-name="delimiter" class="field">
                            {{{delimiterField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Date Format' scope='Import'}}</label>
                        <div data-name="dateFormat" class="field">
                            {{{dateFormatField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Decimal Mark' scope='Import'}}</label>
                        <div data-name="decimalMark" class="field">
                            {{{decimalMarkField}}}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Text Qualifier' scope='Import'}}</label>
                        <div data-name="textQualifier" class="field">
                            {{{textQualifierField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Time Format' scope='Import'}}</label>
                        <div data-name="timeFormat" class="field">
                            {{{timeFormatField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Currency' scope='Import'}}</label>
                        <div data-name="currency" class="field">
                            {{{currencyField}}}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Timezone' scope='Import'}}</label>
                        <div data-name="timezone" class="field">
                            {{{timezoneField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'phoneNumberCountry' category='params' scope='Import'}}</label>
                        <div data-name="phoneNumberCountry" class="field">
                            {{{phoneNumberCountryField}}}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'inIdle' scope='Import' category='messages'}}</label>
                        <div data-name="idleMode" class="field">
                            {{{idleModeField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Skip searching for duplicates' scope='Import'}}</label>
                        <div data-name="skipDuplicateChecking" class="field">
                            {{{skipDuplicateCheckingField}}}
                        </div>
                    </div>
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Silent Mode' scope='Import'}}</label>
                        <div data-name="silentMode" class="field">
                            {{{silentModeField}}}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 form-group cell">
                        <label class="control-label">{{translate 'Run Manually' scope='Import' category='labels'}}</label>
                        <div data-name="manualMode" class="field">
                            {{{manualModeField}}}
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
        <button
            class="btn btn-primary btn-s-wide pull-right hidden"
            data-action="next"
        >{{translate 'Next' scope='Import'}}</button>
        {{/if}}
    </div>

