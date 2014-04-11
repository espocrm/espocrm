<div class="row">
	<div class="col-sm-5">
		<select class="form-control" name="{{typeName}}">
			{{options foreignScopeList foreignScope category='scopeNames'}}
		</select>
	</div>
	<div class="input-group col-sm-7">
		<input class="main-element form-control" type="text" name="{{nameName}}" value="{{nameValue}}" autocomplete="off">
		<span class="input-group-btn">        
		    <button data-action="selectLink" class="btn btn-default" type="button" tabindex="-1"><i class="glyphicon glyphicon-arrow-up"></i></button>
		    <button data-action="clearLink" class="btn btn-default" type="button" tabindex="-1"><i class="glyphicon glyphicon-remove"></i></button>
		</span>
	</div>
</div>
<input type="hidden" name="{{idName}}" value="{{idValue}}">



