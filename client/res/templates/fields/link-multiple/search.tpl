<select class="form-control search-type input-sm" name="{{name}}-type">
    {{options searchTypeList searchType field='searchRanges'}}
</select>

<div class="link-group-container hidden">

    <div class="link-container list-group">
    </div>

    <div class="input-group add-team">
        <input class="main-element form-control" type="text" name="" value="" autocomplete="off" placeholder="{{translate 'Select'}}">
        <span class="input-group-btn">
            <button data-action="selectLink" class="btn btn-default btn-icon btn-sm" type="button" tabindex="-1" title="{{translate 'Select'}}"><span class="fas fa-angle-up"></span></button>
        </span>
    </div>

    <input type="hidden" name="{{name}}Ids" value="{{searchParams.value}}" class="ids">
</div>
