<div class="list-group link-container">
{{#each stageList}}
    <div class="list-group-item form-inline">
        <div style="display: inline-block; width: 100%;">
            <input class="role form-control input-sm pull-right" data-name="{{./this}}" value="{{prop ../values this}}">
            <div>{{./this}}</div>
        </div>
        <br class="clear: both;">
    </div>
{{/each}}
</div>