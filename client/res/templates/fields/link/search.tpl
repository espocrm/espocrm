<select class="form-control search-type input-sm" name="{{name}}-type">
    {{options searchTypeList searchType field='searchRanges'}}
</select>
<div class="primary">
	<div class="input-group">
	    <input class="form-control input-sm" type="text" name="{{nameName}}" value="{{searchParams.valueName}}" autocomplete="off" placeholder="{{translate 'Select'}}">
	    <span class="input-group-btn">
	        <button type="button" class="btn btn-sm btn-default" data-action="selectLink" tabindex="-1" title="{{translate 'Select'}}"><i class="glyphicon glyphicon-arrow-up"></i></button>
	        <button type="button" class="btn btn-sm btn-default" data-action="clearLink" tabindex="-1"><i class="glyphicon glyphicon-remove"></i></button>
	    </span>
	</div>
	<input type="hidden" name="{{idName}}" value="{{searchParams.value}}">
</div>

<div class="one-of-container hidden">
    <div class="link-one-of-container link-container list-group">
    </div>

    <div class="input-group add-team">
        <input class="form-control input-sm element-one-of" type="text" name="" value="" autocomplete="off" placeholder="{{translate 'Select'}}">
        <span class="input-group-btn">
            <button data-action="selectLinkOneOf" class="btn btn-default btn-sm" type="button" tabindex="-1" title="{{translate 'Select'}}"><span class="glyphicon glyphicon-arrow-up"></span></button>
        </span>
    </div>
</div>
