<?php

class FrontController extends FrontControllerCore
{
    public function display()
    {
        // show saved messages
        if (isset($this->context->cookie->redirect_errors)){
            $this->errors = array_merge([$this->context->cookie->redirect_errors], $this->errors);
            unset($this->context->cookie->redirect_errors);
        }
        parent::display();

    }
}