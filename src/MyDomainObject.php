<?php

namespace Crell\Stacker;


/**
 * This class doesn't actually do anything with the XML. It's just to prove a point
 * about overall application structure.
 */
class MyDomainObject
{


    public function __construct(\SimpleXMLElement $xml)
    {
        $this->xml = $xml;
    }

    public function getName()
    {
        return "Larry";
    }
}
