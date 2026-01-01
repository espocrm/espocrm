{{#if header}}
<div class="row button-container">
    <div class="col-sm-4 col-xs-5">
        <div class="btn-group range-switch-group">
            <button class="btn btn-text btn-icon" data-action="prev"><span class="fas fa-chevron-left"></span></button>
            <button class="btn btn-text btn-icon" data-action="next"><span class="fas fa-chevron-right"></span></button>
        </div>
        <div class="btn-group range-switch-group">
        <button class="btn btn-text strong" data-action="today" title="{{todayLabel}}">
            <span class="hidden-sm hidden-xs">{{todayLabel}}</span><span class="visible-sm visible-xs">{{todayLabelShort}}</span>
        </button>
        </div>

        <button
            class="btn btn-text{{#unless isCustomView}} hidden{{/unless}} btn-icon"
            data-action="editCustomView"
            title="{{translate 'Edit'}}"
        ><span class="fas fa-pencil-alt fa-sm"></span></button>
    </div>

    <div class="date-title col-sm-4 col-xs-7">
    <h4><span style="cursor: pointer;" data-action="refresh" title="{{translate 'Refresh'}}"></span></h4></div>

    <div class="col-sm-4 col-xs-12">
        <div class="btn-group pull-right mode-buttons">
            {{{modeButtons}}}
        </div>
    </div>
</div>
{{/if}}

<div class="calendar"></div>
