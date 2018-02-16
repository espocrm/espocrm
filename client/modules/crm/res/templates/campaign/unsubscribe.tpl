<div class="container content">
    <div class="col-md-6 col-md-offset-2 col-sm-8 col-sm-offset-2">
        <div class="panel panel-default">
            <div class="panel-body">
                <p>
                    {{translate 'unsubscribed' category='messages' scope='Campaign'}}
                </p>
                <p>
                    <a class="btn btn-default btn-sm" href="?entryPoint=subscribeAgain&id={{actionData.queueItemId}}">{{translate 'Subscribe again' scope='Campaign'}}</a>
                </p>
            </div>
        </div>
    </div>
</div>
