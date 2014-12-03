<div class="page-header">
    <div class="row">
        <div class="col-sm-7">
            {{#if displayTitle}}
            <h3>{{translate 'Stream'}}</h3>
            {{/if}}
        </div>
        <div class="col-sm-5">
            <div class="pull-right">
                <button class="btn btn-default" data-action="refresh">&nbsp;&nbsp;<span class="glyphicon glyphicon-refresh"></span>&nbsp;&nbsp;</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="list-container">{{{list}}}</div>
    </div>
</div>

