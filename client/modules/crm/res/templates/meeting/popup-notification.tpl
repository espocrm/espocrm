{{#if closeButton}}
<a role="button" tabindex="0" class="pull-right close" data-action="close" aria-hidden="true"><span class="fas fa-times"></span></a>
{{/if}}
<h4>{{header}}</h4>


<div class="cell form-group">
    <div class="field">
        <a
            href="#{{notificationData.entityType}}/view/{{notificationData.id}}"
            data-action="close"
        >{{notificationData.name}}</a>
    </div>

</div>

<div class="cell form-group" data-name="{{dateField}}">
    <div class="field" data-name="{{dateField}}">
        {{{date}}}
    </div>
</div>

