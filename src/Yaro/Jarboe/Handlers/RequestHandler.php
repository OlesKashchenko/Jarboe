<?php 

namespace Yaro\Jarboe\Handlers;


use Yaro\Jarboe\JarboeController;
use Yaro\Jarboe\Interfaces\IObservable;
use Yaro\Jarboe\Interfaces\IObserver;
use Yaro\Jarboe\Observers\EventsObserver;
#use Yaro\Jarboe\Event;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;


class RequestHandler implements IObservable
{

    protected $controller;
    private $observers = array();
    private $event;


    public function __construct(JarboeController $controller)
    {
        $this->controller = $controller;

        /*
        $this->event = new Event();
        $this->event->setUserId(\Sentry::getUser()->getId());
        $this->event->setIp(\Request::getClientIp());
        */

        $this->attachObserver(new EventsObserver());
    } // end __construct

    public function getEvent()
    {
        return $this->event;
    } // end getEvent

    public function setEvent(Event $event)
    {
        $this->event = $event;
    } // end setEvent

    public function handle()
    {
        if (Input::get('edit')) {
            return $this->handleShowEditFormPageAction(Input::get('edit'));
        } elseif (Input::has('make')) {
            return $this->handleShowEditFormPageAction();
        }

        switch (Input::get('query_type')) {
            case 'image_storage':
                return $this->controller->imageStorage->handle();
                
            case 'file_storage':
                return $this->controller->fileStorage->handle();
                
            case 'search':
                return $this->handleSearchAction();
                
            case 'change_order':
                return $this->handleChangeOrderAction();
                
            case 'multi_action':
                return $this->handleMultiAction();
                
            case 'multi_action_with_option':
                return $this->handleMultiActionWithOption();
            
            case 'import':
                return $this->handleImport();
                
            case 'get_import_template':
                return $this->handleImportTemplateDownload();
                
            case 'export':
                return $this->handleExport();
                
            case 'set_per_page':
                return $this->handleSetPerPageAmountAction();
                
            case 'save_inline_form':
                return $this->handleSaveInlineFormAction();

            case 'show_edit_form':
                return $this->handleShowEditFormAction();

            case 'save_edit_form':
                return $this->handleSaveEditFormAction();

            case 'show_add_form':
                return $this->handleShowAddFormAction();

            case 'save_add_form':
                return $this->handleSaveAddFormAction();

            case 'delete_row':
                return $this->handleDeleteAction();
                
            case 'upload_photo':
                return $this->handlePhotoUpload();
            
            case 'upload_photo_wysiwyg':
                return $this->handlePhotoUploadFromWysiwyg();
                
            case 'redactor_image_upload':
                return $this->handlePhotoUploadFromWysiwygRedactor();

            case 'ckeditor_image_upload':
                return $this->handlePhotoUploadFromWysiwygCkeditor();
                
            case 'change_direction':
                return $this->handleChangeDirection();
                
            case 'upload_file':
                return $this->handleFileUpload();
                
            case 'many_to_many_ajax_search':
                return $this->handleManyToManyAjaxSearch();
                
            default:
                return $this->handleShowList();
        }
    } // end handle
    
    private function handleSaveInlineFormAction()
    {
        $this->checkEditPermission();
        
        $idRow = $this->getRowID();
        $fieldType = Input::get('__field_type');
        
        $field = $this->controller->getField($fieldType);
        $errors = $field->doSaveInlineEditForm($idRow, Input::all());

        /*
        $definition = $this->controller->getDefinition();

        $this->event->setAction(Event::ACTION_UPDATE);
        if (isset($definition['db']['table'])) {
            $this->event->setEntityTable($definition['db']['table']);
            $this->event->setEntityId($idRow);
        }

        $this->notifyObserver();
        */

        return Response::json(array(
            'status' => empty($errors),
            'errors' => $errors,
        ));
    } // end handleSaveInlineFormAction
    
    protected function handleManyToManyAjaxSearch()
    {
        $query = Input::get('q');
        $limit = Input::get('limit');
        $page  = Input::get('page');
        $ident = Input::get('ident');
        
        $field = $this->controller->getField($ident);
        
        $data = $field->getAjaxSearchResult($query, $limit, $page);
        
        return Response::json($data);
    } // end handleManyToManyAjaxSearch
    
    protected function handleChangeOrderAction()
    {
        parse_str(Input::get('order'), $order);
        $order = $order['sort'];
        
        $definition = $this->controller->getDefinition();
        
        $info = $definition['db']['pagination']['per_page'];
        if (is_array($info)) {
            $definitionName = $this->controller->getOption('def_name');
            $sessionPath = 'table_builder.'.$definitionName.'.per_page';
            $perPage = Session::get($sessionPath);
            if (!$perPage) {
                $keys = array_keys($info);
                $perPage = $keys[0];
            }
        } else {
            $perPage = $info;
        }
        
        // FIXME: make page param available
        $lowest = (Input::get('page', 1) * $perPage) - $perPage;
        
        foreach ($order as $id) {
            ++$lowest;
            \DB::table($definition['db']['table'])->where('id', $id)->update(array(
                'priority' => $lowest
            ));
        }
        
        return Response::json(array(
            'status' => true
        ));
    } // end handleChangeOrderAction
    
    protected function handleMultiAction()
    {
        // FIXME: move to separate class
        $def = $this->controller->getDefinition();
        
        $type = Input::get('type');
        $action = $def['multi_actions'][$type];
        
        $isAllowed = $action['check'];
        if (!$isAllowed()) {
            throw new \RuntimeException('Multi action not allowed: '. $type);
        }
        
        $ids = Input::get('multi_ids', array());
        $handlerClosure = $action['handle'];
        $data = $handlerClosure($ids);
        
        $data['ids'] = $ids;
        $data['is_hide_rows'] = false;
        if (isset($action['is_hide_rows'])) {
            $data['is_hide_rows'] = $action['is_hide_rows'];
        }
        
        return Response::json($data);
    } // end handleMultiAction
    
    protected function handleMultiActionWithOption()
    {
        // FIXME: move to separate class
        $def = $this->controller->getDefinition();
        
        $type = Input::get('type');
        $option = Input::get('option');
        $action = $def['multi_actions'][$type];
        
        $isAllowed = $action['check'];
        if (!$isAllowed()) {
            throw new \RuntimeException('Multi action not allowed: '. $type);
        }
        
        $ids = Input::get('multi_ids', array());
        $handlerClosure = $action['handle'];
        $data = $handlerClosure($ids, $option);
        
        $data['ids'] = $ids;
        $data['is_hide_rows'] = false;
        if (isset($action['is_hide_rows'])) {
            $data['is_hide_rows'] = $action['is_hide_rows'];
        }
        
        return Response::json($data);
    } // end handleMultiActionWithOption
    
    protected function handleImportTemplateDownload()
    {
        $type = Input::get('type');
        $method = 'do'. ucfirst($type) .'TemplateDownload';
        
        $this->controller->import->$method();
    } // end handleImportTemplateDownload
    
    protected function handleImport()
    {
        $file   = Input::file('file');
        $type   = Input::get('type');
        $method = 'doImport'. ucfirst($type);
        
        $res = $this->controller->import->$method($file);
        
        $response = array(
            'status' => $res
        );
        return Response::json($response);
    } // end handleImport
    
    protected function handleExport()
    {
        $type   = Input::get('type');
        $method = 'doExport'. ucfirst($type);
        $idents = array_keys(Input::get('b', array()));
        
        $this->controller->export->$method($idents);
    } // end handleExport

    protected function handleSetPerPageAmountAction()
    {
        $perPage = Input::get('per_page');
        
        $definitionName = $this->controller->getOption('def_name');
        $sessionPath = 'table_builder.'.$definitionName.'.per_page';
        Session::put($sessionPath, $perPage);
        
        $response = array(
            'url' => $this->controller->getOption('url')
        );
        return Response::json($response);
    } // end handleSetPerPageAmountAction
    
    protected function handleChangeDirection()
    {
        $order = array(
            'direction' => Input::get('direction'),
            'field' => Input::get('field')
        );
        
        $definitionName = $this->controller->getOption('def_name');
        $sessionPath = 'table_builder.'.$definitionName.'.order';
        Session::put($sessionPath, $order);

        $response = array(
            'url' => $this->controller->getOption('url')
        );
        return Response::json($response);
    } // end handleChangeDirection
    
    protected function handleFileUpload()
    {
        // FIXME:
        $file = Input::file('file');
        
        if ($this->controller->hasCustomHandlerMethod('onFileUpload')) {
            $res = $this->controller->getCustomHandler()->onFileUpload($file);
            if ($res) {
                return $res;
            }
        }
        
        $extension = $file->getClientOriginalExtension();
        $fileName  = time() .'_'. \Jarboe::urlify($file->getClientOriginalName()) .'.'. $extension;
        
        $definitionName = $this->controller->getOption('def_name');
        $prefixPath = 'storage/tb-'.$definitionName.'/';
        $postfixPath = date('Y') .'/'. date('m') .'/'. date('d') .'/';
        $destinationPath = $prefixPath . $postfixPath;
        
        $status = $file->move($destinationPath, $fileName);
        
        $data = array(
            'status' => $status,
            'link'   => URL::to($destinationPath . $fileName),
            'short_link' => $destinationPath . $fileName,
        );
        return Response::json($data);
    } // end handleFileUpload
    
    protected function handlePhotoUpload()
    {
        // FIXME:
        $ident = Input::get('ident');
        $file  = Input::file('image');
        $num   = Input::get('num');
        
        $field = $this->controller->getField($ident);
        
        if ($this->controller->hasCustomHandlerMethod('onPhotoUpload')) {
            $res = $this->controller->getCustomHandler()->onPhotoUpload($field, $file);
            if ($res) {
                return $res;
            }
        }
        
        $data = $field->doUpload($file);
        // HACK: for generating multiple image upload template | mb no need
        if ($num) {
            $data['num'] = $data;
        }
        
        return Response::json($data);
    } // end handlePhotoUpload
    
    protected function handlePhotoUploadFromWysiwyg()
    {
        // FIXME:
        $file = Input::file('image');
        
        if ($this->controller->hasCustomHandlerMethod('onPhotoUploadFromWysiwyg')) {
            $res = $this->controller->getCustomHandler()->onPhotoUploadFromWysiwyg($file);
            if ($res) {
                return $res;
            }
        }
        
        $extension = $file->guessExtension();
        $fileName = md5_file($file->getRealPath()) .'_'. time() .'.'. $extension;
        
        $definitionName = $this->controller->getOption('def_name');
        $prefixPath = 'storage/tb-'.$definitionName.'/';
        $postfixPath = date('Y') .'/'. date('m') .'/'. date('d') .'/';
        $destinationPath = $prefixPath . $postfixPath;
        
        $status = $file->move($destinationPath, $fileName);
        
        $data = array(
            'status' => $status,
            'link'   => URL::to($destinationPath . $fileName)
        );
        return Response::json($data);
    } // end handlePhotoUploadFromWysiwyg

    protected function handlePhotoUploadFromWysiwygRedactor()
    {
        $file = Input::file('file');
        
        if ($this->controller->hasCustomHandlerMethod('onPhotoUploadFromWysiwyg')) {
            $res = $this->controller->getCustomHandler()->onPhotoUploadFromWysiwyg($file);
            if ($res) {
                return $res;
            }
        }
        
        $extension = $file->guessExtension();
        $fileName = md5_file($file->getRealPath()) .'_'. time() .'.'. $extension;
        
        $definitionName = $this->controller->getOption('def_name');
        $prefixPath = 'storage/tb-'.$definitionName.'/';
        $postfixPath = date('Y') .'/'. date('m') .'/'. date('d') .'/';
        $destinationPath = $prefixPath . $postfixPath;
        
        $file->move($destinationPath, $fileName);
        
        $data = array(
            'filelink'   => URL::to($destinationPath . $fileName)
        );
        return Response::json($data);
    } // end handlePhotoUploadFromWysiwygRedactor

    protected function handlePhotoUploadFromWysiwygCkeditor()
    {
        $file = Input::file('upload');
        $instance = Input::get('instance');

        if ($this->controller->hasCustomHandlerMethod('onPhotoUploadFromWysiwyg')) {
            $res = $this->controller->getCustomHandler()->onPhotoUploadFromWysiwyg($file);
            if ($res) {
                return $res;
            }
        }

        $extension = $file->guessExtension();
        $fileName = md5_file($file->getRealPath()) .'_'. time() .'.'. $extension;

        $definitionName = $this->controller->getOption('def_name');
        $prefixPath = 'storage/tb-'.$definitionName.'/';
        $postfixPath = date('Y') .'/'. date('m') .'/'. date('d') .'/';
        $destinationPath = $prefixPath . $postfixPath;

        $file->move($destinationPath, $fileName);

        // fime: refactor this wtf!
        return Response::make(
            '<html><body>' .
            '<script src="/js/libs/jquery-1.9.1.js"></script>' .
            '<script type="text/javascript">window.parent.CKEDITOR.instances["'. $instance .'"].insertHtml("<img src=\''. URL::to($destinationPath . $fileName) .'\'>"); ' .
            'jQuery(".cke_dialog_ui_button").each(function() { if (jQuery(this).html() == "Cancel") { jQuery(this).click(); }});</script>' .
            '</body></html>'
        );
    } // end handlePhotoUploadFromWysiwygCkeditor

    protected function handleDeleteAction()
    {
        $idRow = $this->getRowID();
        $this->checkEditPermission();

        $result = $this->controller->query->deleteRow($idRow);

        /*
        $definition = $this->controller->getDefinition();

        $this->event->setAction(Event::ACTION_REMOVE);
        if (isset($definition['db']['table'])) {
            $this->event->setEntityTable($definition['db']['table']);
            $this->event->setEntityId($idRow);
        }
        */

        $this->notifyObserver();

        return Response::json($result);
    } // end handleDeleteAction

    protected function handleShowAddFormAction()
    {
        $result = $this->controller->view->showEditForm();

        return Response::json($result);
    } // end handleShowAddFormAction

    protected function handleSaveAddFormAction()
    {
        $result = $this->controller->query->insertRow(Input::all());
        $result['html'] = $this->controller->view->getRowHtml($result);

        /*
        $definition = $this->controller->getDefinition();

        $this->event->setAction(Event::ACTION_CREATE);
        if (isset($definition['db']['table'])) {
            $this->event->setEntityTable($definition['db']['table']);
        }
        if (isset($result['id'])) {
            $this->event->setEntityId($result['id']);
        }

        $this->notifyObserver();
        */

        return Response::json($result);
    } // end handleSaveAddFormAction

    protected function handleSaveEditFormAction()
    {
        $this->checkEditPermission();

        $result = $this->controller->query->updateRow(Input::all());
        $result['html'] = $this->controller->view->getRowHtml($result);

        /*
        $definition = $this->controller->getDefinition();

        $this->event->setAction(Event::ACTION_UPDATE);
        if (isset($definition['db']['table'])) {
            $this->event->setEntityTable($definition['db']['table']);
        }
        if (isset($result['id'])) {
            $this->event->setEntityId($result['id']);
        }

        $this->notifyObserver();
        */

        return Response::json($result);
    } // end handleSaveEditFormAction

    protected function handleShowEditFormAction()
    {
        $idRow = $this->getRowID();
        $this->checkEditPermission();

        $html = $this->controller->view->showEditForm($idRow);
        $data = array(
            'html' => $html,
            'status' => true
        );

        return Response::json($data);
    } // end handleShowEditFormAction

    protected function checkEditPermission()
    {
        if (!$this->controller->isAllowedID($this->getRowID())) {
            throw new \RuntimeException("Permission denied to perform edit for #{$id}.");
        }
    } // end checkEditPermission

    private function getRowID()
    {
        if (Input::has('id')) {
            return Input::get('id');
        }
        throw new \RuntimeException("Undefined row id for action.");
    } // end getRowID

    protected function handleShowList()
    {
        return array(
            $this->controller->view->showList(),
            $this->controller->view->showEditForm()
        );    
    } // end handleShowList
    
    protected function handleShowEditFormPageAction($id = false)
    {
        return $this->controller->view->showEditFormPage($id);
    } // end handleShowAddFormPageAction

    protected function handleSearchAction()
    {
        $this->_prepareSearchFilters();

        $response = array(
            'url' => $this->controller->getOption('url') .'?catalog='. Input::get('catalog') // HACK: FIXME:
        );
        return Response::json($response);
    } // end handleSearchAction

    private function _prepareSearchFilters()
    {
        $filters = Input::get('filter', array());

        $newFilters = array();
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                if (isset($value['from']) && $value['from']) {
                    $newFilters[$key]['from'] = $value['from'];
                }
                if (isset($value['to']) && $value['to']) {
                    $newFilters[$key]['to'] = $value['to'];
                }
            } else {
                if ($value || $value === '0') {
                    $newFilters[$key] = $value;
                }
            }
        }

        if ($this->controller->hasCustomHandlerMethod('onPrepareSearchFilters')) {
            $this->controller->getCustomHandler()->onPrepareSearchFilters($newFilters);
        }

        $definitionName = $this->controller->getOption('def_name');
        $sessionPath = 'table_builder.'.$definitionName.'.filters';
        Session::put($sessionPath, $newFilters);
    } // end _prepareSearchFilters
    
    public function attachObserver(IObserver $observer)
    {
        $this->observers[] = $observer;
    } // end attachObserver

    public function detachObserver(IObserver $observer)
    {
        $newObservers = array();
        foreach ($this->observers as $obs) {
            if (($obs !== $observer)) {
                $newObservers[] = $obs;
            }
        }

        $this->observers = $newObservers;
    } // end detachObserver

    public function notifyObserver()
    {
        foreach ($this->observers as $obs) {
            $obs->update($this);
        }
    } // end notifyObserver
}
