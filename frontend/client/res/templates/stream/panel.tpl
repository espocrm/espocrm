<div class="form-group">
    <textarea class="note form-control" rows="1" cols="10" placeholder="{{translate 'Write your comment here'}}"></textarea>
    <div class="buttons-panel margin hide floated-row clearfix">
        <div>
            <button class="btn btn-primary post">{{translate 'Post'}}</button>
        </div>
        <div class="attachments-container">
            {{{attachments}}}
        </div>
        <a href="javascript:" class="text-muted pull-right stream-post-info">
        <span class="glyphicon glyphicon-info-sign"></span>
        </a>
    </div>
</div>

<div class="list-container">
    {{{list}}}
</div>
