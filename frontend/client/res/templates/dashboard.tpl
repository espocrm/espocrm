<div class="page-header">
    <div class="row">
        <div class="col-sm-7">
            {{#if displayTitle}}
            <h3>{{translate 'Dashboard' category='scopeNames'}}</h3>
            {{/if}}
        </div>
        <div class="col-sm-5">
            <div class="pull-right">
                <button class="btn btn-default add-dashlet">{{translate 'Add Dashlet'}}</button>
            </div>
        </div>
    </div>
</div>
<div id="dashlets" class="row">{{{dashlets}}}</div>

