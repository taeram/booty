<?php

namespace Taeram;

/**
 * Base Entity Class
 */
abstract class Entity {

    /**
     * The entity name.
     *
     * Used when getting the repository
     *
     * @var string
     */
    protected static $className;

    /**
     * The entity manager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected static $em;

    /**
     * Get the entity manager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEntityManager() {
        return static::$em;
    }

    /**
     * Set the entity manager
     *
     * @param \Doctrine\ORM\EntityManager $em The entity manager
     */
    public static function setEntityManager(\Doctrine\ORM\EntityManager $em) {
        static::$em = $em;
    }

    /**
     * Return the repository for this entity
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public static function getRepository() {
        return self::$em->getRepository(static::$className);
    }

    /**
     * Return the entity by id
     *
     * @param integer $id The entity id
     *
     * @return self
     */
    public static function findById($id) {
        return self::getRepository()->findOneBy(array(
            'id' => $id
        ));
    }

    /**
     * Return all entities
     *
     * @return self
     */
    public static function findAll() {
        return self::getRepository()->findAll();
    }

    /**
     * Delete the entity
     */
     public function delete() {
        self::$em->remove($this);
        self::$em->flush();

        return true;
    }

    /**
     * Save the entity
     *
     * @return boolean
     */
    public function save() {
        self::$em->persist($this);
        self::$em->flush();

        return true;
    }
}
