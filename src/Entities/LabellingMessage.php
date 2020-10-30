<?php

namespace MedSpec\LaravelPostNLAPI\Entities;

class LabellingMessage extends AbstractEntity
{
    protected $Printertype;

    public function __construct(
        $printerType = 'GraphicFile|PDF'
    ) {
        parent::__construct();
        $this->setPrintertype($printerType);
    }
}
