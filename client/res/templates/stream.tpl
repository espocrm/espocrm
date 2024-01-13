<div class="page-header">
    <div class="row">
        <div class="col-sm-7 col-xs-5">
            {{#if displayTitle}}
            <h3>{{translate 'Stream'}}</h3>
            {{/if}}
        </div>
        <div class="col-sm-5 col-xs-7">
            <div class="pull-right btn-group">
                <button
                    class="btn btn-default btn-xs-wide"
                    data-action="createPost"
                ><span class="fas fa-plus fa-sm"></span> {{translate 'Create Post'}}</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="button-container clearfix">
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
                class="btn btn-text btn-icon btn-icon-wide pull-right"
                data-action="refresh"
                title="{{translate 'checkForNewNotes' category='messages'}}"
            ><span class="fas fa-sync-alt fa-sm"></span></button>
        </div>
        <div class="list-container list-container-panel">{{{list}}}</div>
    </div>
</div>
