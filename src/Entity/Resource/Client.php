<?php

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;

class Client extends AbstractEntity
{
    const label     = 'Client';
    const apiEntity = 'client';
    const apiPath   = 'clients';
    const required  = ['name'];
    const readonly  = [
        'id',
        'created_on',
        'updated_on',
        'active',
        'image_thumb_large',
        'image_thumb_medium',
        'image_thumb_small',
        'due_interval',
        'additional_privileges'
    ];
    const includeTypes = [
        'clientcontacts'    => true,
        'projects'          => true,
        'invoices'          => true,
        'recurringprofiles' => true
    ];
    const propTypes = [
        'id'                    => 'integer',
        'name'                  => 'text',
        'address'               => 'text',
        'city'                  => 'text',
        'postal_code'           => 'text',
        'country'               => 'text',
        'state'                 => 'text',
        'phone'                 => 'text',
        'fax'                   => 'text',
        'email'                 => 'email',
        'website'               => 'url',
        'active'                => 'boolean',
        'fiscal_information'    => 'text',
        'image'                 => 'url',
        'image_thumb_large'     => 'url',
        'image_thumb_medium'    => 'url',
        'image_thumb_small'     => 'url',
        'created_on'            => 'datetime',
        'updated_on'            => 'datetime',
        // Undocumented Props
        'due_interval'          => 'integer',
        'additional_privileges' => 'array'
     ];
    const where = [
        'active'    => ['='],
        'name'      => ['=', 'like', 'not like']
    ];
}