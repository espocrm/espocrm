<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Extensions' scope='Admin'}}</h3></div>

{{#if hasUpload}}
<div class="panel panel-default upload">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'selectExtensionPackage' category='messages' scope='Admin'}}</h4>
    </div>
    <div class="panel-body">
        <div>
            <input type="file" name="package" accept="application/zip">
        </div>
        <div
            class="message-container text-danger margin-bottom margin-top"
            style="height: var(--20px);"
        ></div>
        <div class="buttons-container">
            <button
                class="btn btn-primary disabled"
                data-action="upload"
                disabled="disabled"
            >{{translate 'Upload' scope='Admin'}}</button>
        </div>
    </div>
</div>
{{/if}}

<p class="text-danger notify-text hidden"></p>

<div class="list-container">{{{list}}}</div>

