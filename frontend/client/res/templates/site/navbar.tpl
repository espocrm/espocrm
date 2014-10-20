<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">

    <div class="navbar-header">
        <a class="navbar-brand" href="#"><img src="{{logoSrc}}"></a>
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-body">
              <span class="icon-bar"></span>
             <span class="icon-bar"></span>
              <span class="icon-bar"></span>
         </button>
    </div>
    
    <div class="collapse navbar-collapse navbar-body">
        <ul class="nav navbar-nav tabs">            
            {{#each tabs}}
            <li data-name="{{name}}"><a href="{{link}}">{{label}}</a></li>
            {{/each}}
            <li class="dropdown">
                <a id="nav-more-tabs-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#">{{translate 'More'}} <b class="caret"></b></a>                
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-more-tabs-dropdown">                    
                </ul>                
            </li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
            <li class="nav navbar-nav navbar-form global-search-container hidden-xs">
                {{{globalSearch}}}
            </li>        
            <li class="dropdown hidden-xs notifications-badge-container">
                {{{notificationsBadge}}}
            </li>            
            {{#if enableQuickCreate}}
            <li class="dropdown hidden-xs">
                <a id="nav-quick-create-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="glyphicon glyphicon-plus"></i></a>
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-quick-create-dropdown">
                    <li class="dropdown-header">{{translate 'Create'}}</li>
                    {{#each quickCreateList}}
                    <li><a href="#{{./this}}/create" data-name="{{./this}}" data-action="quick-create">{{translate this category='scopeNames'}}</a></li>
                    {{/each}}                                
                </ul>
            </li>
            {{/if}}
            <li class="dropdown">
                <a id="nav-menu-dropdown" class="dropdown-toggle" data-toggle="dropdown" href="#">{{translate 'Menu'}} <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu" aria-labelledby="nav-menu-dropdown">
                    <li><a href="#User/view/{{userId}}">{{userName}}</a></li>
                    <li class="divider"></li>
                    {{#each menu}}
                        {{#unless divider}}
                            <li><a href="{{link}}">{{label}}</a></li>
                        {{else}}
                            <li class="divider"></li>
                        {{/unless}}
                    {{/each}}                    
                </ul>
            </li>
        </ul>
    </div>    
</div>
