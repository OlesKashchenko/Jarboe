<div class="modal fade" id="tree-create-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">

<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                ×
            </button>
            <h4 class="modal-title" id="myModalLabel">Создать ноду</h4>
        </div>
        <div class="modal-body">
            <form id="tree-create-modal-form">
            {{--
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <input type="text" name="title" id="cf-title" class="form-control" placeholder="Название" required="">
                    </div>
                </div>
            </div>
            --}}

                <div class="row">
                    <div class="col-md-12">
                    <div class="tab-pane active">
                        <ul class="nav nav-tabs tabs-pull-right">
                            <label class="label pull-left" style="line-height: 32px;">Название</label>
                            <?php $i = 0; ?>
                            @foreach (\Config::get('jarboe::translate.locales') as $locale)
                                @if ($i == 0)
                                    <li class="active">
                                @else
                                    <li class="">
                                @endif

                                <a href="#title_tab_{{$locale}}" data-toggle="tab">{{$locale}}</a>

                                </li>

                                <?php $i++; ?>
                            @endforeach
                        </ul>

                        <div class="tab-content padding-5">
                            <?php $i = 0; ?>
                            @foreach (\Config::get('jarboe::translate.locales') as $locale)
                                @if ($i == 0)
                                    <div class="tab-pane active" id="title_tab_{{$locale}}">
                                @else
                                    <div class="tab-pane" id="title_tab_{{$locale}}">
                                @endif

                                <div style="position: relative;">
                                    <label style="width: 100%;">
                                        <input type="text" name="title{{$locale == 'ua' ? '' : '_'. $locale}}" placeholder="Название ({{$locale}})" class="dblclick-edit-input form-control unselectable">
                                    </label>
                                </div>
                                </div>

                                <?php $i++; ?>
                            @endforeach
                            </div>
                        </div>
                        </div></div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="cf-template">Шаблон</label>
                        <select class="form-control" id="cf-template" name="template">
                            <option value="">Выберите шаблон</option>
                            <?php /* FIXME: */ $tpls = \Config::get('jarboe::tree.templates', array()); ?>
                            @foreach ($tpls as $capt => $tpl)
                                <option value="{{{ $capt }}}">{{{ $capt }}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tags">Слаг</label>
                        <input type="text" name="slug" class="form-control" id="cf-slug" placeholder="slug">
                    </div>
                </div>
            </div>
            <input type="hidden" name="node" id="cf-node" value="" />
            </form>
        </div>
        <div class="modal-footer">
            <a onclick="Tree.doCreateNode();" href="javascript:void(0);" class="btn btn-success btn-sm">
                <span class="glyphicon glyphicon-floppy-disk"></span> Сохранить
            </a>
            <a href="javascript:void(0);" class="btn btn-default" data-dismiss="modal">
                Отмена
            </a>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div>