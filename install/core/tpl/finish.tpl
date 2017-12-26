<div class="panel-body body">
    <form id="nav">
        <div class="row">
            <div class=" col-md-13">
                <div class="panel-body" align="center">
                    <div class="message">
                        {$langs['labels']['Congratulation! Welcome to EspoCRM']}
                    </div>

                    <br>

                    <div class="likes">
                        <p>
                            {$langs['labels']['share']}
                        </p>
                        <br>

                        <script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
                        <div class="g-plusone" data-size="standard" data-count="true" data-href='http://www.espocrm.com'></div>
                    </div>

                    <div class="more-information">
                        {assign var="blogLink" value="<a target=\"_blank\" href=\"{$config['blog']}\">{$langs['labels']['blog']}</a>"}
                        {assign var="twitterLink" value="<a target=\"_blank\" href=\"{$config['twitter']}\">{$langs['labels']['twitter']}</a>"}
                        {assign var="forumLink" value="<a target=\"_blank\" href=\"{$config['forum']}\">{$langs['labels']['forum']}</a>"}

                        {assign var="message" value="{$langs['labels']['More Information']|replace:'{BLOG}':$blogLink}"}
                        {assign var="message" value="{$message|replace:'{TWITTER}':$twitterLink}"}
                        {assign var="message" value="{$message|replace:'{FORUM}':$forumLink}"}

                        {$message}
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{if $cronHelp}
<div class="cron-help">
    &nbsp;{$cronTitle}
    <pre>
    {$cronHelp}
    </pre>
</div>
{/if}
<footer class="modal-footer">
    <button class="btn btn-primary" type="button" id="start">{$langs['labels']['Go to EspoCRM']}</button>
</footer>
<script>
    {literal}
    $(function(){
    {/literal}
        var langs = {$langsJs};
    {literal}
        var installScript = new InstallScript({action: 'finish', langs: langs});
    })
    {/literal}
</script>