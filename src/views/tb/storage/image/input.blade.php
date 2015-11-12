<div style="overflow: hidden;">
    <div style="float:left; width: 80%;">
        <input type="text" readonly="readonly"
               id="{{$name}}"
               value="{{{ $value }}}" 
               name="{{ $name }}" 
               placeholder="{{{ $placeholder }}}"
               data-type="{{{ $type }}}"
               class="dblclick-edit-input form-control input-sm unselectable">
        </input>
    </div>

    <div style="float:right;width: 9%;margin-left: 1%;">
        <a onclick="TableBuilder.clearStorageImage(this);" style="width:100%;" class="btn btn-info btn-sm" href="javascript:void(0);">Удалить</a>
    </div>

    {{-- fixme: $type = 'image' --}}
    <div style="float:right;width: 9%;">
        <a onclick="TableBuilder.openImageStorageModal(this, 'image');" style="width:100%;" class="btn btn-info btn-sm" href="javascript:void(0);">Выбрать</a>
    </div>

    <div>
        @if ($row && $entity)
            @if ($entity->isImage() && $entity->getSource())

                <img style="height: 90px;" src="{{ asset(cropp($entity->getSource())->fit(90)) }}">

            @elseif ($entity->isGallery())

                <?php $images = $entity->images()->priority()->get(); ?>

                @if ($images)
                    @foreach($images as $image)
                        @if ($loop->index == 5)
                            @break
                        @endif

                        <img style="height: 90px;" src="{{ asset(cropp($image->getSource())->fit(90)) }}">
                    @endforeach
                @else
                    {{ $entity->title }} | {{ $entity->created_at }}
                @endif

            @elseif($entity->isTag())

                {{ $entity->title }} | {{ $entity->created_at }}

            @endif
        @endif
    </div>
</div>
