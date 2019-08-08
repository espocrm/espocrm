<div class="cell form-group">
    <label class="control-label">{{labels.selectFromFiles}}</label>
    <div class="field">
        <input class="note-image-input form-control-file note-form-control note-input" type="file" data-name="files" accept="image/*" multiple="multiple">
    </div>
</div>

<div class="cell form-group">
    <label class="control-label">{{labels.url}}</label>
    <div class="field">
        <div class="input-group">
            <input class="note-image-url form-control note-form-control note-input" type="text" data-name="url">
            <span class="input-group-btn">
                <button class="btn btn-default disabled action" disabled="disabled" data-name="insert" data-action="insert">{{translate 'Insert'}}</button>
            </span>
        </div>
    </div>
</div>
