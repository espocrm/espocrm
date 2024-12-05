<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Upgrade' scope='Admin'}}</h3></div>

<div class="row">
<div class="col-md-8">

<div class="panel panel-danger notify">
    <div class="panel-body">
        <p class="notify-text">
            {{versionMsg}}
            <br><br>
            {{complexText infoMsg inline=true}}
            <br><br>
            {{backupsMsg}}
        </p>
    </div>
</div>

<div class="panel panel-default upload">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'selectUpgradePackage' scope='Admin' category="messages"}}</h4>
    </div>
    <div class="panel-body">
        <p class="text-danger" style="font-weight: 600;">{{{upgradeRecommendation}}}</p>
        <p class="">
            {{complexText downloadMsg inline=true}}
        </p>
        <div>
            <input type="file" name="package" accept="application/zip">
        </div>
        <div class="message-container text-danger" style="height: 20px; margin-bottom: 10px; margin-top: 10px;"></div>
        <div class="buttons-container">
            <button class="btn btn-primary disabled" disabled="disabled" data-action="upload">{{translate 'Upload'}}</button>
        </div>
    </div>
</div>

</div>
</div>
