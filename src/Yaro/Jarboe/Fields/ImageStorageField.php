<?php 

namespace Yaro\Jarboe\Fields;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;


class ImageStorageField extends AbstractField 
{

    public function isEditable()
    {
        return true;
    } // end isEditable

    public function getListValue($row)
    {
        $html = '<span class="glyphicon glyphicon-minus"></span>';
        
        $type = $this->getRequiredAttribute('storage_type');
        $model = '\\' . \Config::get('jarboe::images.models.'. $type);

        if ($type == 'gallery') {
            $entity = $model::with('images')->find($this->getValue($row));
        } else {
            $entity = $model::find($this->getValue($row));
        }

        if ($entity) {
            if ($entity->isImage() && $entity->getSource()) {
                $html = '<img style="height: 90px;" src="'. asset(cropp($entity->getSource())->fit(90)) .'">';
            } elseif ($entity->isGallery()) {
                if ($entity->images->count()) {
                    $html = '<img style="height: 90px;" src="'. asset(cropp($entity->images[0]->getSource())->fit(90)) .'">';
                } else {
                    $html = $entity->title .' | '. $entity->created_at;
                }

            } elseif ($entity->isTag()) {
                $html = $entity->title .' | '. $entity->created_at;
            }
        }
        
        return $html;
    } // end getListValue
    
    public function onSearchFilter(&$db, $value)
    {
        // TODO:
        // FIXME: how? title
    } // end onSearchFilter
    
    public function getEditInput($row = array())
    {
        // FIXME: storage methods
        if ($this->hasCustomHandlerMethod('onGetEditInput')) {
            $res = $this->handler->onGetEditInput($this, $row);
            if ($res) {
                return $res;
            }
        }
        
        $type = $this->getRequiredAttribute('storage_type');
        
        $input = View::make('admin::tb.storage.image.input');
        $input->type  = $type;
        $input->value = $this->getValue($row);
        $input->row   = $row;
        $input->name  = $this->getFieldName();
        $input->caption = $this->getAttribute('caption');
        $input->placeholder = $this->getAttribute('placeholder');

        if ($row) {
            $model = '\\' . \Config::get('jarboe::images.models.'. $type);
            $input->entity = $model::find($this->getValue($row));
        }
        
        return $input->render();
    } // end getEditInput
    
    public function prepareQueryValue($value)
    {
        return (!$value && $this->getAttribute('is_null')) ? null : $value;
    } // end prepareQueryValue

}
