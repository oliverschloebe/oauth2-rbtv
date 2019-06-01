<?php

namespace OliverSchloebe\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class RbtvResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array  $response
     */
    public function __construct(array $response = array())
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->getValueByKey($this->response, 'id');
    }

    /**
     * Get resource owner name
     *
     * @return string|null
     */
    public function getDisplayName()
    {
        return $this->getValueByKey($this->response, 'display_name');
    }

    /**
     * Get resource owner email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getValueByKey($this->response, 'email');
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
