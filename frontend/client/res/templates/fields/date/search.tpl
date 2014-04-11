
<select class="form-control search-type input-sm" name="{{name}}-type">
	{{options searchParams.typeOptions searchParams.type field='dateSearchRanges'}}
</select>
<div class="input-group">
	<input class="main-element form-control input-sm" type="text" name="{{name}}" value="{{searchParams.value1}}" autocomplete="off">
	<span class="input-group-btn">        
        <button type="button" class="btn btn-default btn-sm" tabindex="-1"><i class="glyphicon glyphicon-calendar"></i></button>    
	</span>
</div>
<div class="input-group{{#ifNotEqual searchParams.type 'between'}} hide{{/ifNotEqual}} additional">
	<input class="main-element form-control input-sm" type="text" name="{{name}}-additional" value="{{searchParams.value2}}" autocomplete="off">
	<span class="input-group-btn">        
        <button type="button" class="btn btn-default btn-sm" tabindex="-1"><i class="glyphicon glyphicon-calendar"></i></button>    
	</span>
</div>
