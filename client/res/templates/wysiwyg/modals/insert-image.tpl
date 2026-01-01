<div class="panel panel-default no-side-margin">
    <div class="panel-body">
        <div class="cell form-group">
            <label class="control-label">{{labels.selectFromFiles}}</label>
            <div class="field">
                <label class="attach-file-label" title="{{translate 'Attach File'}}" tabindex="0">
                    <span class="btn btn-default btn-icon"><span class="fas fa-paperclip"></span></span>
                    <input
                        type="file"
                        data-name="files"
                        accept="image/*"
                        tabindex="-1"
                        class="file pull-right"
                    >
                </label>
            </div>
        </div>
        <div class="cell form-group">
            <label class="control-label">{{labels.url}}</label>
            <div class="field">
                <div class="input-group">
                    <input
                        class="note-image-url form-control note-form-control note-input"
                        type="text"
                        data-name="url"
                    >
                    <span class="input-group-btn">
                        <button
                            class="btn btn-default disabled action"
                            disabled="disabled"
                            data-name="insert"
                            data-action="insert"
                        >{{translate 'Insert'}}</button>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
