<?php

namespace Yaro\Jarboe;


class Gallery extends AbstractImageStorage
{
    
    protected $table = 'j_galleries';

    
    public static function flushCache()
    {
        \Cache::tags('j_galleries')->flush();
    } // end flushCache
    
    public function images()
    {
        $model = \Config::get('jarboe::images.models.image');
        
        return $this->belongsToMany($model, 'j_galleries2images', 'id_gallery', 'id_image');
    } // end images
    
    public function tags()
    {
        $model = \Config::get('jarboe::images.models.tag');
        
        return $this->belongsToMany($model, 'j_galleries2tags', 'id_gallery', 'id_tag');
    } // end tags

    public function scopeByTitle($query, $title)
    {
        $title = trim($title);

        if (!$title) {
            return $query;
        }

        return $query->where('title', 'like', '%'. $title .'%');
    } // end scopeByTitle

    public function scopeSearch($query)
    {
        $search = \Session::get('_jsearch_galleries', array());
        foreach ($search as $column => $value) {
            if (!$value) {
                continue;
            }

            if (is_array($value)) {
                if ($column == 'created_at') {
                    $value['to']   = $value['to'] ? : '12/12/2222';
                    $value['from'] = $value['from'] ? : '12/12/1971';

                    // fixme: MARIA DB hack
                    // $from = date('Y-m-d H:i:s', strtotime(preg_replace('~/~', '-', $value['from'])));
                    // $to   = date('Y-m-d H:i:s', strtotime(preg_replace('~/~', '-', $value['to'])));

                    $from = date('Y-m-d 00:00:00', strtotime($value['from']));
                    $to   = date('Y-m-d 23:59:59', strtotime($value['to']));

                    $query->whereBetween($column, array($from, $to));
                }

            } else {
                $query->byTitle($value);
            }
        }

        return $query;
    } // end scopeSearch

    public function isGallery()
    {
        return true;
    } // end isGallery
}
