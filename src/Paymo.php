<?php

namespace Jcolombo\PaymoApiPhp;

/**
*  Paymo
*
*  Base class for connecting to the Paymo App
*
*  @author Joel Colombo
*/
class Paymo {

    /**  @var string $m_SampleProperty define here what this variable is for, do this for every instance variable */
    private $m_SampleProperty = '';
 
    /**
    * Sample method
    *
    * Always create a corresponding docblock for each method, describing what it is for,
    * this helps the phpdocumentator to properly generator the documentation
    *
    * @param string $apiKey A string containing the parameter, do this for each parameter to the function, make sure to make it descriptive
    *
    * @return string
    */
    public function connect($apiKey){
        return "Attempt New Connection to Paymo Now !";
    }

}