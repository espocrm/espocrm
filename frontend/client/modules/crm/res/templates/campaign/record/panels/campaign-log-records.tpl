<div class="row">
	<div class="col-sm-offset-9 col-sm-3 col-xs-offset-6 col-xs-6 filters-container">
		<select name="filter" class="form-control">
		{{{options filterList filterValue field="action" translatedOptions=filterTranslatedOptions scope='CampaignLogRecords'}}}
		</select>
	</div>
</div>
<div class="list-container">{{{list}}}</div>
