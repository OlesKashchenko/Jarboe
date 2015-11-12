<aside id="left-panel">
    <div class="login-info">
        <span>
            <a href="javascript:void(0);">
                <?php $imgClosure = \Config::get('jarboe::admin.user_image'); ?>
                <img src="{{ $imgClosure() }}" alt="me" class="online"/>
                <span>
                    <?php $nameClosure = \Config::get('jarboe::admin.user_name'); ?>
                    {{ $nameClosure() }}
                </span>
            </a>
        </span>
    </div>
    <nav>
        <ul>{{ \Yaro\Jarboe\Facades\Jarboe::fetchNavigation() }}</ul>
    </nav>
    <span class="minifyme" data-action="minifyMenu">
        <i class="fa fa-arrow-circle-left hit"></i>
    </span>
</aside>
