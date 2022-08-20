<div class="form-group">
    <a
        role="button"
        tabindex="0"
        class="remove-filter pull-right"
        data-name="{{name}}"
    >{{#unless notRemovable}}<i class="fas fa-times"></i>{{/unless}}</a>
    <label class="control-label small" data-name="{{name}}">{{translate name category='fields' scope=scope}}</label>
    <div class="field" data-name="{{name}}">{{{field}}}</div>
</div>


