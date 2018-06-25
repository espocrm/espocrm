<div>
    <button class="btn btn-default pull-right" data-action="selectIcon" title="{{translate 'Select'}}"><span class="glyphicon glyphicon-arrow-up"></span></button>
    <span style="vertical-align: middle;">
        {{#if value}}
        <span class="{{value}}"></span>
        {{else}}
        {{translate 'None'}}
        {{/if}}
    </span>
</div>