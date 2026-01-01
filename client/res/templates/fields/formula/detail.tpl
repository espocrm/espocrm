{{#if isNotEmpty}}
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div id="{{containerId}}">{{value}}</div>
    </div>
</div>
{{else}}<span class="none-value">{{translate 'None'}}</span>{{/if}}
