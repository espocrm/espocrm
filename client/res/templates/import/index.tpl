<div class="page-header">
	<div class="row">
	    <div class="col-lg-7 col-sm-7">
	    	<h3>
	    	{{#if fromAdmin}}
    		<a href="#Admin">{{translate 'Administration' scope='Admin'}}</a>
    		<span class="breadcrumb-separator"><span></span></span>
		   	{{/if}}
		   	{{translate 'Import' category='scopeNames'}}
	   		</h3>
	    </div>
	    <div class="col-lg-5 col-sm-5">
	        <div class="header-buttons btn-group pull-right">
				<a href="#Import/list" class="btn btn-default">{{translate 'Import Results' scope='Import'}}</a>
	        </div>
	    </div>
	</div>
</div>

<div class="import-container">
    {{{step}}}
</div>

