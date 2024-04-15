<div class="page-header">
    <div class="row">
        <div class="col-sm-7">
            <h3>{{translate 'Notifications'}}</h3>
        </div>
        <div class="col-sm-5">
            <div class="pull-right btn-group">
                <button
                    class="btn btn-text"
                    data-action="markAllNotificationsRead"
                    title="{{translate 'Mark all read'}}"
                >{{translate 'Mark all read'}}</button>
                <button
                    class="btn btn-text btn-xs-wide btn-icon"
                    data-action="refresh"
                    title="{{translate 'checkForNewNotifications'
                    category='messages'}}"
                ><span class="fas fa-sync"></span>&nbsp;</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="list-container notification-list list-container-panel">{{{list}}}</div>
    </div>
</div>
