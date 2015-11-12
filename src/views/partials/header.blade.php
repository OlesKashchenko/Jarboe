<header id="header">
    <div id="logo-group">
        <span id="logo" style="margin-top: 10px;"> <img style="height: 30px;width: auto;" src="{{\Config::get('jarboe::admin.logo_url')}}" alt="{{{ \Config::get('jarboe::admin.caption') }}}"> </span>
        {{ \Yaro\Jarboe\Facades\Jarboe::fetchInformer() }}
    </div>

    <div class="pull-right">
        <div id="hide-menu" class="btn-header pull-right">
            <span> <a href="javascript:void(0);" data-action="toggleMenu" title="Collapse Menu"><i class="fa fa-reorder"></i></a> </span>
        </div>

        <div id="logout" class="btn-header transparent pull-right">
            <span> <a href="/admin/logout" title="Выход" data-action="userLogout" data-logout-msg="Вы можете увеличить безопасноть, закрыв браузер после выхода"><i class="fa fa-sign-out"></i></a> </span>
        </div>

        <div id="search-mobile" class="btn-header transparent pull-right">
            <span> <a href="javascript:void(0)" title="Search"><i class="fa fa-search"></i></a> </span>
        </div>
    </div>
</header>
