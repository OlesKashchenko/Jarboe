
<div class="j-tags-preloader" style="
    display:none;
    background-color: #666;
    height: 100%;
    width: 100%;
    position: absolute;
    z-index: 4;
    text-align: center;
    left: 0;
    top: 0;
    opacity: 0.5;
"><i class="fa fa-gear fa-4x fa-spin" style="
    color: #fff;
    position: fixed;
    left: 50%;
    margin-left: -65px;
    top: 50%;
    font-size: 60px;
"></i></div>

<div class="row">


<table class="j-tags-table table table-bordered table-striped">
    <thead>
        <tr>
            <th width="1%">#</th>
            <th style="text-align: right;"><input style="width:30%;" type="text" name="title" /></th>
            <th width="1%">
                <a href="javascript:void(0);" class="btn btn-default btn-sm" 
                   onclick="Superbox.addTag(this);">
                    Добавить
                </a>
            </th>
            @if ($type == 'tag')
            <th></th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($tags as $tag)
            @include('admin::tb.storage.image.tag_row')
        @endforeach
    </tbody>
</table>
    

</div>
