<form id="dashlet-options">
    <div class="row">
        <div class="form-group col-md-6">
            <label class="control-label">{{translate 'Title'}}</label>
            <input type="text" class="form-control" name="title" value="{{options.title}}">    
        </div>
        <div class="form-group col-md-6">
            <label class="control-label">{{translate 'Display Records'}}</label>
            <select class="form-control" name="displayRecords" value="{{options.displayRecords}}">    
                <option value="5"{{#ifEqual options.displayRecords 5}} selected{{/ifEqual}}>5</option>
                <option value="10"{{#ifEqual options.displayRecords 10}} selected{{/ifEqual}}>10</option>
                <option value="15"{{#ifEqual options.displayRecords 15}} selected{{/ifEqual}}>15</option>
                <option value="20"{{#ifEqual options.displayRecords 20}} selected{{/ifEqual}}>20</option>
            </select>
        </div>
    </div>
</form>
