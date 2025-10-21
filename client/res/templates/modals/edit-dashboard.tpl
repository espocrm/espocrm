<div class="no-side-margin record">
    <div>
        <div class="record-grid-wide">
            <div class="left">
                <div class="middle">
                    <div class="panel panel-default first last">
                        <div class="panel-body panel-body-form">
                            <div class="row">
                                <div class="cell form-group col-md-6" data-name="dashboardTabList">
                                    <label
                                        class="control-label"
                                        data-name="dashboardTabList"
                                    >{{translate 'dashboardTabList' category='fields' scope="Preferences"}}</label>
                                    <div class="field" data-name="dashboardTabList">
                                        {{{dashboardTabList}}}
                                    </div>
                                </div>
                                {{#if hasLocked}}
                                    <div class="cell form-group col-md-6" data-name="dashboardLocked">
                                        <label
                                            class="control-label"
                                            data-name="dashboardLocked"
                                        >{{translate 'dashboardLocked' category='fields' scope="Preferences"}}</label>
                                        <div class="field" data-name="dashboardLocked">
                                            {{{dashboardLocked}}}
                                        </div>
                                    </div>
                                {{/if}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

