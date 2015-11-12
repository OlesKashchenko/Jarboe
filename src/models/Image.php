<?php

namespace Yaro\Jarboe;


class Image extends AbstractImageStorage
{
    
    protected $table = 'j_images';
    
    public static function flushCache()
    {
        \Cache::tags('j_images')->flush();
    } // end flushCache
    
    public function scopePriority($query, $direction = 'asc')
    {
        return $query->orderBy('priority', $direction);
    } // end priority

    public function scopeByTags($query, $tags = array())
    {
        if (!$tags) {
            return $query;
        }

        $relatedImagesIds = \DB::table('j_images2tags')->whereIn('id_tag', $tags)->lists('id_image');

        return $query->whereIn('id', $relatedImagesIds);
    } // end scopeByTags

    public function scopeByGalleries($query, $galleries = array())
    {
        if (!$galleries) {
            return $query;
        }

        $relatedImagesIds = \DB::table('j_galleries2images')->whereIn('id_gallery', $galleries)->lists('id_image');

        return $query->whereIn('id', $relatedImagesIds);
    } // end scopeByGalleries

    public function scopeByTitle($query, $title)
    {
        if (!$title) {
            return $query;
        }

        return $query->where('info', 'like', '%'. $title .'%');
    } // end scopeByTitle

    public function getInfo($values = false)
    {
        $info = $values ? : $this->info;
        return preg_replace('~"~', "~", $info);
    } // end getInfo
    
    public function getSource($ident = '')
    {
        $ident = $ident ? '_' . $ident : '';
        $source = 'source' . $ident;

        return $this->$source;
    } // end getSource
    
    public function tags()
    {
        $model = \Config::get('jarboe::images.models.tag');
        
        return $this->belongsToMany($model, 'j_images2tags', 'id_image', 'id_tag');
    } // end tags
    
    public function get($ident, $localePostfix = false)
    {
        $postfix = $localePostfix ? '_'. \App::getLocale() : '';
        $ident = $ident . $postfix;
        
        $info = json_decode($this->info, true);
        
        if (!$info || !array_key_exists($ident, $info)) {
            return '';
        }
        
        return $info[$ident];
    } // end get
    
    public function scopeSearch($query)
    {
        $search = \Session::get('_jsearch_images', array());
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
                } else if ($column == 'tags') {
                    $query->byTags($value);
                } else if ($column == 'galleries') {
                    $query->byGalleries($value);
                }

            } else {
                if ($column == 'title') {
                    $query->byTitle($value);
                } else {
                    $query->where($column, 'like', '%'. $value .'%');
                }
            }
        }

        //\Session::forget('_jsearch_images');

        return $query;
    } // end scopeSearch
    
    public function isImage()
    {
        return true;
    } // end isImage
}
