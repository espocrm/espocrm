<link href="client/modules/crm/css/fullcalendar.css" rel="stylesheet">
<link href="client/modules/crm/css/fullcalendar.print.css" rel="stylesheet" media="print">
<link href="client/modules/crm/css/calendar.css" rel="stylesheet">

{{#if header}}
<div class="row button-container">
    <div class="col-sm-4 col-xs-3">
        <div class="btn-group">
            <button class="btn btn-default" data-action="prev"><span class="glyphicon glyphicon-chevron-left"></span></button>
            <button class="btn btn-default" data-action="next"><span class="glyphicon glyphicon-chevron-right"></span></button>
        </div>
        <button class="btn btn-default hidden-xs" data-action="today">{{translate 'Today' scope='Calendar'}}</button>
    </div>

    <div class="date-title col-sm-4 col-xs-4"><h4><span style="cursor: pointer;" data-action="refresh" title="{{translate 'Refresh'}}"></span></h4></div>

    <div class="col-sm-4 col-xs-5">
        <div class="btn-group pull-right">
            {{#each ../modeList}}
            <button class="btn btn-default{{#ifEqual this ../../mode}} active{{/ifEqual}}" data-action="mode" data-mode="{{./this}}">{{translate this scope='Calendar' category='modes'}}</button>
            {{/each}}
        </div>
    </div>
</div>
{{/if}}

<div class="calendar"></div>



