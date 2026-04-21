<?php

namespace EvolutionCMS;

abstract class TemplateController implements \EvolutionCMS\Interfaces\TemplateController
{
    private $view = '';
    private $viewData = [];

    public final function getViewData(): array
    {
        return $this->viewData;
    }

    public final function addViewData(...$data)
    {
        if(empty($data)) return;
        if(count($data) === 1 && is_array($data[0])) {
            $this->viewData = array_merge($this->viewData, $data[0]);
        } else {
            foreach ($data as $key) {
                if(!is_string($key)) continue;
                $method = 'get' . $key;
                if (method_exists($this, $method)) {
                    $this->viewData[$key] = $this->$method();
                }
            }
        }
    }

    public final function getView(): string
    {
        return $this->view;
    }

    public final function setView(string $view)
    {
        $this->view = $view;
    }

    public function process()
    {

    }

    public final function __isset(string $property)
    {
        return isset($this->viewData[$property]);
    }

    public final function __get($property)
    {
        if (!empty($property) && array_key_exists($property, $this->viewData)) {
            return $this->viewData[$property];
        }
    }

    public final function __set($property, $value)
    {
        if (!empty($property)) {
            $this->viewData[$property] = $value;
        }
    }
}

