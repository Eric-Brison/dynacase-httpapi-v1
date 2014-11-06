<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 06/11/14
 * Time: 18:25
 */

namespace Dcp\HttpApi\V1\Crud;


class TrashCollection extends DocumentCollection {

    protected function prepareSearchDoc()
    {
        $this->_searchDoc = new \SearchDoc();
        $this->_searchDoc->setObjectReturn();
        $this->_searchDoc->trash = "only";
    }

    public function generateURL($path, $query = null)
    {
        $path = str_replace("documents/", "trash/", $path);
        return parent::generateURL($path, $query);
    }

} 