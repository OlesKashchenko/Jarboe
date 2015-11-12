<section>
    <div class="tab-pane active">

        <ul class="nav nav-tabs tabs-pull-right">
            <label class="label pull-left" style="line-height: 32px;">{{$caption}}</label>
            @foreach ($tabs as $tab)
                @if ($loop->first)
                    <li class="active">
                @else
                    <li class="">
                @endif

                    <a href="#{{$pre .  $name . $tab['postfix']}}" data-toggle="tab">{{$tab['caption']}}</a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content padding-5">
            @foreach ($tabs as $tab)

                @if ($loop->first)
                    <div class="tab-pane active" id="{{ $pre . $name . $tab['postfix']}}">
                @else
                    <div class="tab-pane" id="{{ $pre . $name . $tab['postfix']}}">
                @endif

                    <textarea id="{{$pre . $name . $tab['postfix']}}-wysiwyg" name="{{ $name . $tab['postfix'] }}" class="ckeditor">{{ $tab['value'] }}</textarea>

                    <script type="text/javascript">
                        jQuery(document).ready(function() {
                            CKEDITOR.on('instanceCreated', function(e) {
                                if (e.editor.name === '{{$pre . $name . $tab['postfix']}}-wysiwyg') {
                                    e.editor.on('change', function(event) {
                                        jQuery('textarea#{{$pre . $name . $tab['postfix']}}-wysiwyg').val(e.editor.getData());
                                    });
                                }
                            });

                            <?php

                            $editorButtons = Config::get('jarboe::wysiwyg.ckeditor.buttons');

                            foreach ($editorButtons as $buttonIdent => $buttonValue) {
                                if ($buttonValue) {
                                    unset($editorButtons[$buttonIdent]);
                                }
                            }

                            $editorButtons = array_keys($editorButtons);
                            $editorButtons = implode(',', $editorButtons)

                            ?>

                            CKEDITOR.config.removeButtons = '{{$editorButtons}}';
                            CKEDITOR.config.extraPlugins = "ImageManager";
                            CKEDITOR.config.height = "400";
                            CKEDITOR.config.baseFloatZIndex = "99999";

                            CKEDITOR.replace('{{$pre . $name . $tab['postfix']}}-wysiwyg');
                        });
                    </script>
                </div>
            @endforeach
        </div>
    </div>
</section>
