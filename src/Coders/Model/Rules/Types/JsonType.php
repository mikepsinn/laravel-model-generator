<?php

namespace Reliese\Coders\Model\Rules\Types;


class JsonType
{
    use _Common;
    use _Strings;

    public $col;
    public $rules = [];

    public function __invoke($col)
    {
        $this->setCol($col);

        $this->nullable();
        $this->json();

        return $this->rules;
    }

    protected function json()
    {
        $this->rules['json'] = null;
    }

}
