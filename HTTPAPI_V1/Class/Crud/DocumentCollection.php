<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 03/11/14
 * Time: 14:12
 */

namespace Dcp\HttpApi\V1\Crud;


class DocumentCollection extends Crud
{

    const GET_PROPERTIES = "document.properties";
    const GET_PROPERTY = "document.properties.";
    const GET_ATTRIBUTES = "document.attributes";
    const GET_ATTRIBUTE = "document.attributes.";

    protected $defaultFields = null;
    protected $returnFields = null;
    protected $slice = 0;
    protected $offset = 0;
    protected $orderBy = "";
    /**
     * @var \SearchDoc
     */
    protected $_searchDoc = null;

    public function __construct()
    {
        parent::__construct();
        $this->defaultFields = self::GET_PROPERTIES;
    }

    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You need to use the family collection to create document");
        throw $exception;
    }

    /**
     * Read a ressource
     * @param string|int $resourceId Resource identifier
     * @return mixed
     */
    public function read($resourceId)
    {
        $documentList = $this->prepareDocumentList();
        $return = array(
            "requestParameters" => array(
                "slice" => $this->slice,
                "offset" => $this->offset,
                "nbResult" => count($documentList),
                "orderBy" => $this->orderBy
            )
        );
        $return["uri"] = $this->generateURL("documents/");
        $documentFormatter = $this->prepareDocumentFormatter($documentList);
        $data = $documentFormatter->format();
        foreach ($data as &$currentData) {
            if (isset($currentData["properties"]["revision"])) {
                $currentData["properties"]["revision"] = intval($currentData["properties"]["revision"]);
            }
            if (isset($currentData["properties"]["state"]) && !$currentData["properties"]["state"]->reference) {
                unset($currentData["properties"]["state"]);
            }
            $currentData["uri"] = $this->generateURL(sprintf("documents/%d.json", $currentData["properties"]["initid"]));
        }
        $return["documents"] = $data;

        return $return;
    }

    /**
     * Update the ressource
     * @param string|int $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot update all the documents");
        throw $exception;
    }

    /**
     * Delete ressource
     * @param string|int $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot delete all the documents.");
        throw $exception;
    }

    /**
     * Get the restricted attributes
     *
     * @throws Exception
     * @return array
     */
    protected function getAttributeFields()
    {
        $prefix = self::GET_ATTRIBUTE;
        $fields = $this->getFields();
        if ($this->hasFields(self::GET_ATTRIBUTE)) {
            return DocumentUtils::getAttributesFields(null, $prefix, $fields);
        }
        return array();
    }

    /**
     * Get the restrict fields value
     *
     * The restrict fields is used for restrict the return of the get request
     *
     * @return array|null
     */
    protected function getFields()
    {
        if ($this->returnFields === null) {
            if (!empty($this->contentParameters["fields"])) {
                $fields = $this->contentParameters["fields"];
            } else {
                $fields = $this->defaultFields;
            }
            if ($fields) {
                $this->returnFields = array_map("trim", explode(",", $fields));
            } else {
                $this->returnFields = array();
            }
        }
        return $this->returnFields;
    }

    /**
     * Get the list of the properties required
     *
     * @return array
     */
    protected function _getPropertiesId()
    {
        $properties = array();
        $returnFields = $this->getFields();
        $subField = self::GET_PROPERTY;
        foreach ($returnFields as $currentField) {
            if (strpos($currentField, $subField) === 0) {
                $properties[] = substr($currentField, mb_strlen(self::GET_PROPERTY));
            }
        }
        return $properties;
    }

    /**
     * Check if the current restrict field exist
     *
     * @param $fieldId
     * @param string $subField
     * @return bool
     */
    protected function hasFields($fieldId, $subField = '')
    {
        $returnFields = $this->getFields();
        if (in_array($fieldId, $returnFields)) {
            return true;
        }

        if ($subField) {
            foreach ($returnFields as $aField) {
                if (strpos($aField, $subField) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function prepareSearchDoc() {
        $this->_searchDoc = new \SearchDoc();
        $this->_searchDoc->setObjectReturn();
    }

    public function prepareDocumentList()
    {
        $this->prepareSearchDoc();
        $this->slice = isset($this->contentParameters["slice"]) ?
            $this->contentParameters["slice"]
            : \ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "COLLECTION_DEFAULT_SLICE");
        $this->slice = intval($this->slice);
        $this->_searchDoc->setSlice($this->slice);
        $this->offset = isset($this->contentParameters["offset"]) ? $this->contentParameters["offset"] : 0;
        $this->offset = intval($this->offset);
        $this->_searchDoc->setStart($this->offset);
        $this->orderBy = $this->extractOrderBy();
        $this->_searchDoc->setOrder($this->orderBy);
        return $this->_searchDoc->getDocumentList();
    }

    protected function extractOrderBy()
    {
        $orderBy = isset($this->contentParameters["orderBy"]) ? $this->contentParameters["orderBy"] : "title:asc";
        return DocumentUtils::extractOrderBy($orderBy);
    }

    /**
     * @param $documentList
     * @return DocumentFormatter
     * @throws \Dcp\HttpApi\V1\DocManager\Exception
     */
    protected function prepareDocumentFormatter($documentList)
    {
        $documentFormatter = new DocumentFormatter($documentList);
        if ($this->hasFields(self::GET_PROPERTIES)) {
            $documentFormatter->useDefaultProperties();
        } else {
            $documentFormatter->setProperties($this->_getPropertiesId());
        }
        $documentFormatter->setAttributes($this->getAttributeFields());
        return $documentFormatter;
    }
}