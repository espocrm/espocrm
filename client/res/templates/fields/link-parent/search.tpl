<select class="form-control search-type input-sm">
    {{options searchTypeList searchType field='searchRanges'}}
</select>
<div class="primary">
    <select class="form-control input-sm entity-type" data-name="{{typeName}}">
        {{options foreignScopeList searchData.typeValue category='scopeNames'}}
    </select>
    <div class="input-group">
        <input class="form-control input-sm" type="text" data-name="{{nameName}}" value="{{searchData.nameValue}}" autocomplete="espo-{{name}}" placeholder="{{translate 'Select'}}">
        <span class="input-group-btn">
            <button type="button" class="btn btn-sm btn-default btn-icon" data-action="selectLink" tabindex="-1" title="{{translate 'Select'}}"><i class="fas fa-angle-up"></i></button>
            <button type="button" class="btn btn-sm btn-default btn-icon" data-action="clearLink" tabindex="-1"><i class="fas fa-times"></i></button>
        </span>
    </div>
    <input type="hidden" data-name="{{idName}}" value="{{searchData.idValue}}">
</div>
