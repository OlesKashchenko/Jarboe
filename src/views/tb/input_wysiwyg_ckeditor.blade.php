<textarea id="{{$name}}-wysiwyg" name="{{ $name }}" class="ckeditor">{{ $value }}</textarea>
<script type="text/javascript">
    jQuery(document).ready(function() {
        CKEDITOR.on('instanceCreated', function (e) {
            if (e.editor.name === '{{$name}}-wysiwyg') {
                e.editor.on('change', function (event) {
                    jQuery('textarea#' + e.editor.name).val(e.editor.getData());
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

        CKEDITOR.replace('{{$name}}-wysiwyg');
    });
</script>
