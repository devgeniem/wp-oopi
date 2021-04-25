<?php

namespace Geniem\Oopi\Attribute;


use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\Importable;

class PostThumbnail implements Attribute {

    public function set_importable( Importable $importable ) : Attribute {

        return $this;
    }

    public function save() {
        // TODO: Implement save() method.
    }
}
