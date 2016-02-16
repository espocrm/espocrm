<div class="input-group">
    <span class="input-group-btn">
        <select class="form-control" name="{{typeName}}">
            {{options foreignScopeList foreignScope category='scopeNames'}}
        </select>
    </span>
    <input class="main-element form-control" type="text" name="{{nameName}}" value="{{nameValue}}" autocomplete="off" placeholder="{{translate 'Select'}}">
    <span class="input-group-btn">
        <button data-action="selectLink" class="btn btn-default" type="button" tabindex="-1" title="{{translate 'Select'}}"><i class="glyphicon glyphicon-arrow-up"></i></button>
        <button data-action="clearLink" class="btn btn-default" type="button" tabindex="-1"><i class="glyphicon glyphicon-remove"></i></button>
    </span>
</div>
<input type="hidden" name="{{idName}}" value="{{idValue}}">
