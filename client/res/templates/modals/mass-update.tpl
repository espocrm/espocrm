{{#unless fields}}
    {{translate 'No fields available for Mass Update'}}
{{else}}    
<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" tabindex="-1">{{translate 'Select Field'}} <span class="caret"></span></button>
        <ul class="dropdown-menu pull-left filter-list">
        {{#each ../fields}}
            <li><a href="javascript:" data-name="{{./this}}" data-action="add-field">{{translate this scope=../../scope category='fields'}}</a></li>
        {{/each}}
        </ul>
    </div>
</div>
{{/unless}}
<div class="row">
    <div class="fields-container"></div>
</div>
