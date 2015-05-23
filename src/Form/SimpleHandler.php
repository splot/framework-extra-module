<?php
namespace Splot\FrameworkExtraModule\Form;

use MD\Foundation\Utils\ArrayUtils;

use Knit\Entity\AbstractEntity;
use Knit\Entity\Repository;
use Knit\Exceptions\DataValidationFailedException;
use Knit\Knit;

class SimpleHandler
{

    protected $knit;

    protected $httpCodeKey = '__code';

    public function __construct(Knit $knit, $httpCodeKey = '__code') {
        $this->knit = $knit;
        $this->httpCodeKey = $httpCodeKey;
    }

    /**
     * @return AbstractEntity
     */
    public function handleEntityCreation($entityClass, array $allowedProperties, array $data, array $additional = array()) {
        $data = ArrayUtils::filterKeys($data, $allowedProperties);
        $repository = $this->knit->getRepository($entityClass);

        try {
            $data = array_merge($data, $additional);
            $entity = $repository->createWithData($data);
            $repository->save($entity);
        } catch(DataValidationFailedException $e) {
            $errors = array();

            foreach($e->getErrors() as $error) {
                $errors[$error->getProperty()] = $error->getFailedValidators();
            }

            return array(
                $this->httpCodeKey => 400,
                'errors' => $errors
            );
        }

        return $entity;
    }

    public function handleEntityUpdate(AbstractEntity $entity, array $allowedProperties, array $data) {
        $data = ArrayUtils::filterKeys($data, $allowedProperties);
        $repository = $this->knit->getRepository($entity->__getClass());

        try {
            $entity->updateWithData($data);
            $repository->save($entity);
        } catch(DataValidationFailedException $e) {
            $errors = array();

            foreach($e->getErrors() as $error) {
                $errors[$error->getProperty()] = $error->getFailedValidators();
            }

            return array(
                $this->httpCodeKey => 400,
                'errors' => $errors
            );
        }

        return array();
    }

}