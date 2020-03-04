<?php

namespace Jcolombo\PaymoApiPhp\Entity;

class Project extends _AbstractEntity
{

    const label    = 'Project';
    const apiEntity = 'project';
    const apiPath  = 'projects';
    const required = ['name'];
    const readonly = ['id', 'task_code_increment', 'created_on', 'updated_on' , 'billing_type'];
    const includeTypes = [
        'client'=>false,
        'projectstatus'=>false,
        'tasklists'=>true,
        'tasks'=>true,
        'milestones'=>true,
        'discussions'=>true,
        'files'=>true,
        'invoiceitem'=>false,
        'workflow'=>false
    ];
    const propTypes = [
        'id' => 'integer',
        'name' => 'text',
        'code' => 'text',
        'task_code_increment' => 'integer',
        'description' => 'text',
        'client_id' => 'entity:client',
        'status_id' => 'entity:projectstatus',
        'active' => 'boolean',
        'color' => 'text',
        'users' => 'entityList:users',
        'managers' => 'entityList:managers',
        'billable' => 'boolean',
        'flat_billing' => 'boolean',
        'price_per_hour' => 'decimal',
        'price' => 'decimal',
        'estimated_price' => 'decimal',
        'hourly_billing_mode' => 'text',
        'budget_hours' => 'decimal',
        'adjustable_hours' => 'boolean',
        'invoiced' => 'boolean',
        'invoice_item_id' => 'entity:invoiceitem',
        'workflow_id' => 'entity:milestone',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        // Undocumented Props
        'billing_type' => 'text',
    ];
    const where = [
        'client_id' => ['='],
        'active'    => ['='],
        'users'     => ['=', 'in', 'not in'],
        'managers'  => ['=', 'in', 'not in'],
        'billable'  => ['='],
    ];
}