<?php


namespace Jcolombo\PaymoApiPhp;

use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Utility\RequestAbstraction;


class Request
{

    static function compileIncludeParameter($include) {
        if (!$include || !is_array($include) || count($include) < 1) {
            return null;
        }
        return join(',', $include);
    }

    static function fetch(Paymo $connection, $objectKey, $id, $select, $include) {
        if (!is_array($select)) {
            $select = !is_null($select) ? [$select] : [];
        }
        if (!is_array($include)) {
            $include = !is_null($include) ? [$include] : [];
        }
        $includedEntities = [];
        foreach($include as $i) {
            $incEntity = explode('.', $i)[0];
            if (!in_array($incEntity, $includedEntities)) {
                $includedEntities[] = $incEntity;
            }
        }
        $validProps = array_merge($select, $includedEntities);

        $request = new RequestAbstraction();
        $request -> method = 'GET';
        $request -> resourceUrl = $objectKey."/{$id}";
        $request -> includeEntities = Request::compileIncludeParameter($include);

        //var_dump($select);
        //var_dump($include);
        //exit;

        // Populate RequestAbstraction object to normalize for execution

        $response = $connection -> execute($request);
        if (
            $response->success
            && $response->body
            && is_array($response->body->$objectKey)
            && count($response->body->$objectKey) > 0
        ) {
            $selectAll = count($select) === 0;
            $singleEntity = $response->body->$objectKey[0];
            //echo "RESPONSE BODY...\n";
            //var_dump($singleEntity);
            if (!$selectAll) {
                foreach($singleEntity as $k => $v) {
                    if (!($k==='id' || in_array($k, $validProps))) {
                        unset($singleEntity->$k);
                    }
                }
            }
            //var_dump($singleEntity); exit;
            return $singleEntity;
        }

        return false;
    }


}