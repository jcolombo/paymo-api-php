<?php

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;

class ProjectStatus extends AbstractEntity
{
    const label     = 'Project Status';
    const apiEntity = 'projectstatus';
    const apiPath   = 'projectstatuses';
    const required  = ['name'];
    const readonly  = [
        'id',
        'created_on',
        'updated_on',
        'readonly',
    ];
    const includeTypes = [
        'projects' => true,
    ];
    const propTypes = [
        'id'         => 'integer',
        'name'       => 'text',
        'active'     => 'boolean',
        'seq'        => 'integer',
        'readonly'   => 'boolean',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        // Undocumented Props

     ];
    // =, >, >=, <, <=, !=, like, not like, in (value1,value2,...), not in (value1, value2)
    const where = [
        'active'   => ['='],
        'name'     => ['=', 'like', 'not like'],
        'readonly' => ['=']
    ];
}