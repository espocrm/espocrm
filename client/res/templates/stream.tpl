<div class="page-header">
    <div class="row">
        <div class="col-sm-7 col-xs-5">
            {{#if displayTitle}}
            <h3><span
                data-action="fullRefresh"
                style="user-select: none; cursor: pointer"
            >{{translate 'Stream'}}</span></h3>
            {{/if}}
        </div>
        <div class="col-sm-5 col-xs-7">
            <div class="pull-right btn-group">
                <button
                    class="btn btn-default btn-xs-wide"
                    data-action="createPost"
                ><span class="fas fa-plus fa-sm"></span><span>{{translate 'Create Post'}}</span></button>
                {{#if hasMenu}}
                    <button
                        class="btn btn-default dropdown-toggle"
                        data-toggle="dropdown"
                    ><span class="fas fa-ellipsis-h"></span></button>
                    <ul class="dropdown-menu pull-right">
                        {{#if hasGlobalStreamAccess}}
                        <li>
                            <a
                                role="button"
                                tabindex="0"
                                href="#GlobalStream"
                            >{{translate 'GlobalStream' category='scopeNames'}}</a>
                        </li>
                        {{/if}}
                    </ul>
                {{/if}}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="button-container clearfix" tabindex="-1">
            <div class="btn-group">
                {{#each filterList}}
                    <button
                        class="btn btn-text btn-xs-wide {{#ifEqual this ../filter}} active{{/ifEqual}}"
                        data-action="selectFilter"
                        data-name="{{./this}}"
                    >{{translate this scope='Note' category='filters'}}</button>
                {{/each}}
            </div>
            <button
                class="btn btn-text btn-icon pull-right"
                data-action="refresh"
                title="{{translate 'checkForNewNotes' category='messages'}}"
            ><span class="fas fa-sync-alt fa-sm icon"></span></button>
        </div>
        <div class="list-container list-container-panel">{{{list}}}</div>
    </div>
</div>
