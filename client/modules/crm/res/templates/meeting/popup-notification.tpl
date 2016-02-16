{{#if closeButton}}
<a href="javascript:" class="pull-right close" data-action="close" aria-hidden="true">×</a>
{{/if}}
<h4>{{header}}</h4>


<div class="cell form-group">
    <div class="field">
        <a href="#{{notificationData.entityType}}/view/{{notificationData.id}}" data-action="close">{{notificationData.name}}</a>
    </div>

</div>

<div class="cell form-group" data-name="dateStart">
    <div class="field" data-name="dateStart">
        {{{dateStart}}}
    </div>
</div>

