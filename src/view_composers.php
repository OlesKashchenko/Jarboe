<?php

// image storage
View::composer('admin::tb.storage.image.tags', function($view) {
    $model = Config::get('jarboe::images.models.tag');
    $tags = $model::orderBy('id', 'desc')->get();
    $view->with('tags', $tags);
});

View::composer('admin::tb.storage.image.galleries', function($view) {
    $model = Config::get('jarboe::images.models.gallery');
    $galleries = $model::search()
                    ->orderBy('id', 'desc')
                    ->get();
    
    $view->with('galleries', $galleries)->with('type', 'gallery');
});

View::composer('admin::tb.storage.image.images', function($view) {
    // FIXME:
    $fields  = Config::get('jarboe::images.image.fields');
    $perPage = Config::get('jarboe::images.per_page');
    
    $model  = Config::get('jarboe::images.models.image');
    $images = $model::search()
                    ->orderBy('created_at', 'desc')
                    ->skip(0)
                    ->limit($perPage)
                    ->get();

    $view->with('images', $images)->with('fields', $fields);
});

// file storage
View::composer('admin::tb.storage.file.files', function($view) {
    $model = Config::get('jarboe::files.models.file');
    $files = $model::orderBy('id', 'desc')->get();
    $view->with('files', $files);
});

View::composer(
    [
        'admin::tb.storage.image.images_search',
        'admin::tb.storage.image.images_operations'
    ],
    function($view) {

    $tagModel  = Config::get('jarboe::images.models.tag');
    $galleryModel = Config::get('jarboe::images.models.gallery');

    $galleries = $galleryModel::orderBy('id', 'desc')->get();
    $tags = $tagModel::all();

    $view->with('tags', $tags)->with('galleries', $galleries);
});
