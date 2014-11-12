
<div class="form-group">
    <a href="javascript:" class="remove-filter pull-right" data-name="{{name}}">{{#unless notRemovable}}<i class="glyphicon glyphicon-remove"></i>{{/unless}}</a>
    <label class="cotrol-label small">{{translate name category='fields' scope=scope}}</label>
    <div class="field field-{{name}}">{{{field}}}</div>
</div>


