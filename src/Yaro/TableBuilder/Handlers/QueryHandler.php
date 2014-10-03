<?php 

namespace Yaro\TableBuilder\Handlers;

use Yaro\TableBuilder\TableBuilderController;
use Yaro\TableBuilder\Exceptions\TableBuilderValidationException as TableBuilderValidationException;
use Yaro\TableBuilder\Exceptions\TableBuilderPreValidationException as TableBuilderPreValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;


class QueryHandler {

    protected $controller;

    protected $db;
    protected $dbOptions;

    public function __construct(TableBuilderController $controller)
    {
        $this->controller = $controller;

        $definition = $controller->getDefinition();

        $this->dbOptions = $definition['db'];
    } // end __construct

    protected function getOptionDB($ident)
    {
        return $this->dbOptions[$ident];
    } // end getOptionDB

    protected function hasOptionDB($ident)
    {
        return isset($this->dbOptions[$ident]);
    } // end hasOptionDB

    public function getRows()
    {
        $this->db = DB::table($this->dbOptions['table']);

        $this->prepareSelectValues();
        $this->prepareFilterValues();

        $this->onSearchFilterQuery();

        $definitionName = $this->controller->getOption('def_name');
        $sessionPath = 'table_builder.'.$definitionName.'.order';
        $order = Session::get($sessionPath, array());
        if ($order) {
            $this->db->orderBy($this->getOptionDB('table') .'.'. $order['field'], $order['direction']);
        } else if ($this->hasOptionDB('order')) {
            $order = $this->getOptionDB('order');
            foreach ($order as $field => $direction) {
                $this->db->orderBy($this->getOptionDB('table') .'.'. $field, $direction);
            }
        }

        if ($this->hasOptionDB('pagination')) {
            $pagination = $this->getOptionDB('pagination');
            $paginator = $this->db->paginate($pagination['per_page']);
            $paginator->setBaseUrl($pagination['uri']);
            return $paginator;
        }
        return $this->db->get();
    } // end getRows
    
    protected function prepareFilterValues()
    {
        $definition = $this->controller->getDefinition();
        $filters = isset($definition['filters']) ? $definition['filters'] : array();
        
        foreach ($filters as $name => $field) {
            $this->db->where($name, $field['sign'], $field['value']);
        }
    } // end prepareFilterValues
    
    protected function doPrependFilterValues(&$values)
    {
        $definition = $this->controller->getDefinition();
        $filters = isset($definition['filters']) ? $definition['filters'] : array();
        
        foreach ($filters as $name => $field) {
            $values[$name] = $field['value'];
        }
    } // end doPrependFilterValues
    
    protected function prepareSelectValues()
    {
        $this->db->select($this->getOptionDB('table') .'.id');

        $fields = $this->controller->getFields();
        foreach ($fields as $name => $field) {
            $field->onSelectValue($this->db);
        }
    } // end prepareSelectValues

    public function getRow($id)
    {
        $this->db = DB::table($this->getOptionDB('table'));

        $this->prepareSelectValues();

        $this->db->where($this->getOptionDB('table').'.id', $id);

        return $this->db->first();
    } // end getRow

    public function getTableAllowedIds()
    {
        $this->db = DB::table($this->getOptionDB('table'));
        
        $this->prepareFilterValues();
        
        $ids = $this->db->lists('id');

        return $ids;
    } // end getTableAllowedIds

    protected function onSearchFilterQuery()
    {
        $definitionName = $this->controller->getOption('def_name');
        $sessionPath = 'table_builder.'.$definitionName.'.filters';

        $filters = Session::get($sessionPath, array());
        foreach ($filters as $name => $value) {
            if ($this->controller->hasCustomHandlerMethod('onSearchFilter')) {
                $res = $this->controller->getCustomHandler()->onSearchFilter($this->db, $name, $value);
                if ($res) {
                    continue;
                }
            }

            $this->controller->getField($name)->onSearchFilter($this->db, $value);
        }
    } // end onSearchFilterQuery

    public function updateFastRow($values)
    {
        $this->_checkFastSaveValues($values);
        $this->_checkField($values, $values['name']);

        $value = $this->controller->getField($values['name'])->prepareQueryValue($values['value']);
        $updateData = array(
            $values['name'] => $value
        );
        $updateResult = $this->db->where('id', $values['id'])->update($updateData);

        $res = array(
            'status' => $updateResult,
            'id'     => $values['id'],
            'value'  => $values['value']
        );
        if ($this->controller->hasCustomHandlerMethod('onUpdateFastRowResponse')) {
            $this->controller->getCustomHandler()->onUpdateFastRowResponse($res);
        }

        return $res;
    } // end updateFastRow

    public function updateRow($values)
    {
        if (!$this->controller->actions->isAllowed('update')) {
            throw new \RuntimeException('Update action is not permitted');
        }
        
        $updateData = $this->_getRowQueryValues($values);
        $this->_checkFields($updateData);
        
        if ($this->controller->hasCustomHandlerMethod('onUpdateRowData')) {
            $this->controller->getCustomHandler()->onUpdateRowData($updateData);
        }
        $this->doValidate($updateData);
        
        $this->doPrependFilterValues($updateData);
        
        $status = $this->db->where('id', $values['id'])->update($updateData);
        
        $this->onManyToManyValues($values, $values['id']);
	
        $res = array(
            'id'     => $values['id'],
            'values' => $updateData
        );
        if ($this->controller->hasCustomHandlerMethod('onUpdateRowResponse')) {
            $this->controller->getCustomHandler()->onUpdateRowResponse($res);
        }

        return $res;
    } // end updateRow

    public function deleteRow($id)
    {
        if (!$this->controller->actions->isAllowed('delete')) {
            throw new \RuntimeException('Delete action is not permitted');
        }
        
        $res = $this->db->where('id', $id)->delete();

        $res = array(
            'id' => $id,
            'status' => $res
        );
        if ($this->controller->hasCustomHandlerMethod('onDeleteRowResponse')) {
            $this->controller->getCustomHandler()->onDeleteRowResponse($res);
        }

        return $res;
    } // end deleteRow

    public function insertRow($values)
    {
        if (!$this->controller->actions->isAllowed('insert')) {
            throw new \RuntimeException('Insert action is not permitted');
        }
        
        $insertData = $this->_getRowQueryValues($values);
        $this->_checkFields($insertData);
        
        if ($this->controller->hasCustomHandlerMethod('onInsertRowData')) {
            $this->controller->getCustomHandler()->onInsertRowData($insertData);
        }
        $this->doValidate($insertData);
        
        $this->doPrependFilterValues($insertData);
        
        $id = $this->db->insertGetId($insertData);
        
        $this->onManyToManyValues($values, $id);

        $res = array(
            'id'     => $id,
            'values' => $insertData
        );
        if ($this->controller->hasCustomHandlerMethod('onInsertRowResponse')) {
            $this->controller->getCustomHandler()->onInsertRowResponse($res);
        }

        return $res;
    } // end insertRow
    
    private function onManyToManyValues($values, $id)
    {
        // FIXME:
        if (isset($values['many2many'])) {
            $field = $this->controller->getField('many2many');
            $field->onPrepareRowValues($values['many2many'], $id);
        }
    } // end onManyToManyValues
    
    private function doValidate($values)
    {
        // FIXME:
        /*
        foreach ($values as $ident => $value) {
            $field = $this->controller->getField($ident);
            $field->doValidate($value);
        }
        */
        $errors = array();
        
        $definition = $this->controller->getDefinition();
        $fields = $definition['fields'];
        foreach ($fields as $ident => $options) {
            try {
                $field = $this->controller->getField($ident);
                $tabs = $field->getAttribute('tabs');
                if ($tabs) {
                    foreach ($tabs as $tab) {
                        $fieldName = $ident . $tab['postfix'];
                        $field->doValidate($values[$fieldName]);
                    }
                } else {
                    if (isset($values[$ident])) {
                        $field->doValidate($values[$ident]);
                    }
                }
            } catch (TableBuilderPreValidationException $e) {
                $errors = array_merge($errors, explode('|', $e->getMessage()));
                continue;
            }
        }
        
        if ($errors) {
            $errors = implode('|', $errors);
            throw new TableBuilderValidationException($errors);
        }
    } // end doValidate

    private function _getRowQueryValues($values)
    {
        $values = $this->_unsetFutileFields($values);
        /*
        array_walk($values, function(&$value, $ident) {
            $field = $this->controller->getField($ident);
            $value = $field->prepareQueryValue($value);
        }); 
        */
        $definition = $this->controller->getDefinition();
        $fields = $definition['fields'];
        foreach ($fields as $ident => $options) {
            $field = $this->controller->getField($ident);
            $tabs = $field->getAttribute('tabs');
            if ($tabs) {
                foreach ($tabs as $tab) {
                    $fieldName = $ident . $tab['postfix'];
                    $values[$fieldName] = $field->prepareQueryValue($values[$fieldName]);
                }
            } else {
                if (isset($values[$ident])) {
                    $values[$ident] = $field->prepareQueryValue($values[$ident]);
                }
            }
        }

        return $values;
    } // end _getRowQueryValues

    private function _unsetFutileFields($values)
    {
        unset($values['id']);
        unset($values['query_type']);
        unset($values['many2many']);
        
        return $values;
    } // end _unsetFutileFields

    private function _checkFields($values)
    {
        $definition = $this->controller->getDefinition();
        $fields = $definition['fields'];
        foreach ($fields as $ident => $options) {
            $field = $this->controller->getField($ident);
            $tabs = $field->getAttribute('tabs');
            if ($tabs) {
                foreach ($tabs as $tab) {
                    $this->_checkField($values, $ident, $field);
                }
            } else {
                if (isset($values[$ident])) {
                    $this->_checkField($values, $ident, $field);
                }
            }
        }
    } // end _checkFields

    private function _checkField($values, $ident, $field)
    {
        if (!$field->isEditable()) {
            throw new \RuntimeException("Field [{$ident}] is not editable");
        }
    } // end _checkField

    private function _checkFastSaveValues($values)
    {
        $required = array(
            'id', 'name', 'value'
        );

        foreach ($required as $ident) {
            if (!isset($values[$ident])) {
                throw new \RuntimeException("FastSave ident [{$ident}] does not pass.");
            }
        }
    } // end _checkFastSaveValues

}
