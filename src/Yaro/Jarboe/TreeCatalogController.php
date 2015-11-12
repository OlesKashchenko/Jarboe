<?php 

namespace Yaro\Jarboe;

use Yaro\Jarboe\Interfaces\IObservable;
use Yaro\Jarboe\Interfaces\IObserver;
use Yaro\Jarboe\Observers\EventsObserver;
use Yaro\Jarboe\Entities\Event;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;


class TreeCatalogController implements IObservable
{
    protected $model;
    protected $options;
    private $observers = array();
    private $event;
    
    public function __construct($model, array $options)
    {
        $this->model   = $model;
        $this->options = $options;

        if (\Config::get('jarboe::log.enabled')) {
            $this->event = new Event();
            $this->event->setUserId(\Sentry::getUser()->getId());
            $this->event->setIp(\Request::getClientIp());

            $this->attachObserver(new EventsObserver());
        }
    } // end __construct

    public function getEvent()
    {
        return $this->event;
    } // end getEvent

    public function setEvent(Event $event)
    {
        $this->event = $event;
    } // end setEvent

    // FIXME:
    public function setOptions(array $options = array())
    {
        $this->options = $options;
    } // end setOptions

    public function handle()
    {

        switch (Input::get('query_type')) {
            
            case 'do_create_node':
                return $this->doCreateNode();
            
            case 'do_change_active_status':
                return $this->doChangeActiveStatus();
                
            case 'do_change_position':
                return $this->doChangePosition();
            
            case 'do_delete_node':
                return $this->doDeleteNode();

            case 'clone_record':
                return $this->doCloneNode();

            case 'do_rebuild_tree':
                return $this->doRebuildTree();
                
            case 'do_edit_node':
                return $this->doEditNode();
                                
            case 'do_update_node':
                return $this->doUpdateNode();
                
            case 'get_edit_modal_form':
                return $this->getEditModalForm();

            case 'ckeditor_image_upload':
                return $this->handlePhotoUploadFromWysiwygCkeditor();

            default:
                return $this->handleShowCatalog();
        }
    } // end handle
    
    public function doUpdateNode()
    {
        $model = $this->model;
        
        switch (Input::get('name')) {
            case 'template':
                $node = $model::find(Input::get('pk'));
                $node->template = Input::get('value');
                $node->save();
                break;
            
            default:
                throw new \RuntimeException('someone tries to hack me :c');
        }
        
        $model::flushCache();
    } // end doUpdateNode
        
    public function doCreateNode()
    {
        $activeField = \Config::get('jarboe::tree.node_active_field.field');
        $locales = \Config::get('jarboe::translate.locales');
        $options = \Config::get('jarboe::tree.node_active_field.options', false);
        $model = $this->model;
        
        $root = $model::find(Input::get('node', 1));

        $node = new $model();
        $node->parent_id = Input::get('node', 1);

        foreach ($locales as $locale) {
            if ($locale == 'ua') {
                $node->title = Input::get('title');
            }
        }

        $node->template  = Input::get('template');
        $node->$activeField = $options ? '' : '0';

        $node->save();

        $node->slug = Input::get('slug') ? : Input::get('title');
        $node->save();

        // fixme:
        // $node->makeChildOf($root);

        $model::rebuild();
        $model::flushCache();

        // log action
        if (\Config::get('jarboe::log.enabled')) {
            $this->event->setAction(Event::ACTION_CREATE);
            $this->event->setEntityTable($node->getTable());
            $this->event->setEntityId($node->id);

            $this->notifyObserver();
        }

        return Response::json(array(
            'status' => true, 
        ));
    } // end doCreateNode
    
    public function doChangeActiveStatus()
    {
        $activeField = \Config::get('jarboe::tree.node_active_field.field');
        $options = \Config::get('jarboe::tree.node_active_field.options', array());
        $model = $this->model;
        
        $node = $model::find(Input::get('id'));
        
        $value = Input::get('is_active');
        if ($options) {
            $value = implode(array_filter(array_keys(Input::get('onoffswitch', array()))), ',');
        }
        $node->$activeField = $value;
        
        $node->save();
        
        $model::flushCache();

        // log action
        if (\Config::get('jarboe::log.enabled')) {
            $this->event->setAction(Event::ACTION_CHANGE_ACTIVE_STATUS);
            $this->event->setEntityTable($node->getTable());
            $this->event->setEntityId($node->id);

            $this->notifyObserver();
        }

        return Response::json(array(
            'axtive' => true
        ));
    } // end doChangeActiveStatus
    
    public function doChangePosition()
    {
        $model = $this->model;
        
        $id = Input::get('id');
        $idParent = Input::get('parent_id', 1);
        $idLeftSibling  = Input::get('left_sibling_id');
        $idRightSibling = Input::get('right_sibling_id');
        
        $item = $model::find($id);
        $root = $model::find($idParent);
        
        $prevParentID = $item->parent_id;
        $item->makeChildOf($root);
        
        $item->slug = $item->slug;
        $item->save();
        
        if ($prevParentID == $idParent) {
            if ($idLeftSibling) {
                $item->moveToRightOf($model::find($idLeftSibling));
            } elseif ($idRightSibling) {
                $item->moveToLeftOf($model::find($idRightSibling));
            }
        }
        
        $model::rebuild();
        $model::flushCache();

        $item = $model::find($item->id);

        // log action
        if (\Config::get('jarboe::log.enabled')) {
            $this->event->setAction(Event::ACTION_CHANGE_POSITION);
            $this->event->setEntityTable($item->getTable());
            $this->event->setEntityId($item->id);

            $this->notifyObserver();
        }

        $data = array(
            'status' => true, 
            'item' => $item, 
            'parent_id' => $root->id
        );
        return Response::json($data);
    } // end doChangePosition
    
    // FIXME: fix me, fix
    public function process()
    {
        $model = $this->model;

        $idNode  = Input::get('__node', Input::get('node', 1));
        $current = $model::find($idNode);

        $templates = Config::get('jarboe::tree.templates');
        $template = Config::get('jarboe::tree.default');
        if (isset($templates[$current->template])) {
            $template = $templates[$current->template];
        }
        
        $options = array(
            'url'      => URL::current(),
            'def_name' => 'tree.'. $template['node_definition'],
            'additional' => array(
                'node'    => $idNode,
                'current' => $current,
            )
        );
        if ($template['type'] == 'table') {
            $options['def_name'] = 'tree.'. $template['definition'];
        }
        
        return \Jarboe::table($options);
    } // end process
    
    public function doDeleteNode()
    {
        $model = $this->model;

        $item = $model::find(Input::get('id'));
        $status = $model::destroy(Input::get('id'));
        $model::flushCache();

        // log action
        if (\Config::get('jarboe::log.enabled')) {
            $this->event->setAction(Event::ACTION_REMOVE);
            $this->event->setEntityTable($item->getTable());
            $this->event->setEntityId($item->id);

            $this->notifyObserver();
        }

        return Response::json(array(
            'status' => $status
        ));   
    } // end doDeleteNode
    
    private function handleShowCatalog()
    {
        $model = $this->model;
        
        $tree = $model::all()->toHierarchy();
        
        $idNode  = Input::get('node', 1);
        $current = $model::find($idNode);

        $parentIDs = array();
        foreach ($current->getAncestors() as $anc) {
            $parentIDs[] = $anc->id;
        }

        $templates = Config::get('jarboe::tree.templates');
        $template = Config::get('jarboe::tree.default');
        if (isset($templates[$current->template])) {
            $template = $templates[$current->template];
        }
        
        if ($template['type'] == 'table') {
            $options = array(
                'url'      => URL::current(),
                'def_name' => 'tree.'. $template['definition'],
                'additional' => array(
                    'node'    => $idNode,
                    'current' => $current,
                )
            );
            list($table, $form) = \Jarboe::table($options);
            $content = View::make('admin::tree.content', compact('current', 'table', 'form', 'template'));
        } elseif (false && $current->isLeaf()) {
            $content = 'ama leaf';
        } else {
            $content = View::make('admin::tree.content', compact('current', 'template'));
        }
        
        return View::make('admin::tree', compact('tree', 'content', 'current', 'parentIDs'));
    } // end handleShowCatalog
    
    public function getEditModalForm()
    {
        $model = $this->model;
        
        $idNode = Input::get('id');
        $current = $model::find($idNode);
        $templates = Config::get('jarboe::tree.templates');
        $template = Config::get('jarboe::tree.default');
        if (isset($templates[$current->template])) {
            $template = $templates[$current->template];
        }
        
        $options = array(
            'url'      => URL::current(),
            'def_name' => 'tree.'. $template['node_definition'],
            'additional' => array(
                'node'    => $idNode,
                'current' => $current,
            )
        );
        $controller = new JarboeController($options);
        
        $html = $controller->view->showEditForm($idNode, true);
        
        return Response::json(array(
            'status' => true,
            'html' => $html
        ));
    } // end getEditModalForm
    
    public function doEditNode()
    {
        $model = $this->model;
        
        $idNode    = Input::get('id');
        $current   = $model::find($idNode);
        $templates = Config::get('jarboe::tree.templates');
        $template  = Config::get('jarboe::tree.default');
        if (isset($templates[$current->template])) {
            $template = $templates[$current->template];
        }

        $options = array(
            'url'        => URL::current(),
            'def_name'   => 'tree.'. $template['node_definition'],
            'additional' => array(
                'node'    => $idNode,
                'current' => $current,
            )
        );
        $controller = new JarboeController($options);
        
        
        $result = $controller->query->updateRow(Input::all());
        $model::flushCache();
        
        $item = $model::find($idNode);
        $result['html'] = View::make('admin::tree.content_row', compact('item'))->render();

        // log action
        if (\Config::get('jarboe::log.enabled')) {
            $this->event->setAction(Event::ACTION_UPDATE);
            $this->event->setEntityTable($item->getTable());
            $this->event->setEntityId($item->id);

            $this->notifyObserver();
        }

        return Response::json($result);   
    } // end doEditNode

    protected function handlePhotoUploadFromWysiwygCkeditor()
    {
        $file = Input::file('upload');
        $instance = Input::get('instance');

        $extension = $file->guessExtension();
        $fileName = md5_file($file->getRealPath()) .'_'. time() .'.'. $extension;

        $prefixPath = 'storage/tb-tree/upload/';
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

    public function doCloneNode($id = 0, $parent_id = 0)
    {
        $model = $this->model;
        $idNode = Input::get('id');

        if ($id) {
            $idNode = $id;
        }

        $data = $model::find($idNode);

        $data->slug = $data->slug.time();
        $data->title = $data->title;
        $data->is_active = "";

        if ($parent_id) {
            $data->parent_id = $parent_id;
        }

        $newItem = $data->replicate();
        $newItem->save();

        $children = $data->children()->get();

        if (count($children)) {
           foreach ($children as $child) {
               $this->doCloneNode($child->id, $newItem->id);
           }

        } else {
           $model::flushCache();

           echo "ok";
        }
    }

    public function doRebuildTree()
    {
        $model = $this->model;
        $model::rebuild();

        return Response::json(array('status' => true));
    }
}
