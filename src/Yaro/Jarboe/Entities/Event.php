<?php

namespace Yaro\Jarboe\Entities;


class Event extends \Eloquent
{

    protected $table = 'events_log';

    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_REMOVE = 'remove';
    const ACTION_CHANGE_POSITION = 'change_position';
    const ACTION_CHANGE_ACTIVE_STATUS = 'change_active_status';


    public function getUserId()
    {
        return $this->id_user;
    } // end getUserId

    public function setUserId($id)
    {
        $this->id_user = $id;
    } // end setUserId

    public function getIp()
    {
        return $this->ip;
    } // end getIp

    public function setIp($ip)
    {
        $this->ip = $ip;
    } // end setIp

    public function getAction()
    {
        return $this->action;
    } // end getAction

    public function setAction($action)
    {
        $this->action = $action;
    } // end setAction

    public function getEntityTable()
    {
        return $this->entity_table;
    } // end getEntityTable

    public function setEntityTable($table)
    {
        $this->entity_table = $table;
    } // end setEntityTable

    public function getEntityId()
    {
        return $this->id_entity;
    } // end getEntityId

    public function setEntityId($id)
    {
        $this->id_entity = $id;
    } // end setEntityId

    public function getInfo()
    {
        return $this->info;
    } // end getInfo

    public function setInfo($info)
    {
        $this->info = $info;
    } // end setInfo
}
