<tr class="image-storage-edit-gallery-tr">
    <td colspan="6">
        <form class="form-horizontal" style="margin-bottom: 20px;">
            <fieldset>
                <legend>
                    #{{$tag->id}}: {{$tag->title}}
                    <a onclick="Superbox.closeGalleryContentForm();" style="float: right;" class="btn btn-xs bg-color-blueDark txt-color-white" href="javascript:void(0);">
                        <i class="fa fa-times"></i>
                    </a>
                </legend>

                @if ($tag->images->count())
                    <ul id="sortable">
                        @foreach ($tag->images as $image)
                            <li class="ui-state-default" id="{{$image->id}}">
                                <img class="j-image-dblclk" src="{{asset(cropp($image->source)->fit(80))}}" style="height: 80px; width: 80px;"/>
                                <a onclick="Superbox.deleteTagImageRelation(this, {{$image->id}}, {{$tag->id}});" style="position: absolute; right: 0; bottom: 0; width: 100%;" href="javascript:void(0);" class="btn btn-default btn-xs">Удалить</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    Нет изображений, связанных с данным тегом
                @endif

            </fieldset>
        </form>

        <style>
            #sortable {
                list-style-type: none;
                margin: 0;
                padding: 0;
                width: 100%;
            }
            #sortable li {
                margin: 3px 3px 3px 0;
                padding: 1px;
                float: left;
                width: 90px;
                height: 110px;
                font-size: 4em;
                text-align: center;
                cursor: all-scroll;
            }
            .ui-state-highlight {
                width: 90px;
                height: 90px;
            }
        </style>
    </td>
</tr>
