<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Extensions' scope='Admin'}}</h3></div>

<div class="panel panel-default upload">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'selectExtensionPackage' category='messages' scope='Admin'}}</h4>
    </div>
    <div class="panel-body">
        <div>
            <input type="file" name="package" accept="application/zip">
        </div>
        <div class="message-container text-danger" style="height: 20px; margin-bottom: 10px; margin-top: 10px;"></div>
        <div class="buttons-container">
            <button class="btn btn-primary disabled" data-action="upload">{{translate 'Upload' scope='Admin'}}</button>
        </div>
    </div>
</div>

<p class="text-danger notify-text hidden"></p>

<div class="list-container">{{{list}}}</div>

