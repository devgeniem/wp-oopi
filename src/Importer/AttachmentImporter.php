<?php

namespace Geniem\Oopi\Importer;

use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;

class AttachmentImporter implements \Geniem\Oopi\Interfaces\Importer {

    /**
     * @inheritDoc
     */
    public function import( Importable $importable, ?ErrorHandler $error_handler = null ): ?int {
        // TODO: Implement import() method.
    }

    /**
     * @inheritDoc
     */
    public function identify() {
        // TODO: Implement identify() method.
    }
}