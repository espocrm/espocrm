

<div class="row">
    <div class="col-md-10">
        <div id="{{containerId}}">{{{value}}}</div>
    </div>
    <div class="col-md-2">
        <div class="button-container">
            <div class="btn-group">
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-plus"></span></button>
                <ul class="dropdown-menu pull-right">
                    <li><a href="javascript:" data-action="addAttribute">{{translate 'Attribute'}}</a></li>
                    <li><a href="javascript:" data-action="addFunction">{{translate 'Function'}}</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>