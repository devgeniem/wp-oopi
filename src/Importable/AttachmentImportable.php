<?php

namespace Geniem\Oopi\Importable;

use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Traits\ImportableLanguage;
use Geniem\Oopi\Traits\ImporterAccessing;
use Geniem\Oopi\Traits\ImportStatus;

class AttachmentImportable implements \Geniem\Oopi\Interfaces\Importable {

    /**
     * The error scope.
     */
    const ESCOPE = 'attachment';

    /**
     * Use the language attribute.
     */
    use ImportableLanguage;

    /**
     * Use the importer property.
     */
    use ImporterAccessing;

    /**
     * Feature for checking if the importable is imported already.
     */
    use ImportStatus;

    /**
     * @inheritDoc
     */
    public function __construct( string $oopi_id, ?Importer $importer = null, ?ErrorHandler $error_handler = null ) {
    }

    /**
     * @inheritDoc
     */
    public function set_data( $data ) {
        // TODO: Implement set_data() method.
    }

    /**
     * @inheritDoc
     */
    public function get_oopi_id(): string {
        // TODO: Implement get_oopi_id() method.
    }

    /**
     * @inheritDoc
     */
    public function get_wp_id(): ?int {
        // TODO: Implement get_wp_id() method.
    }

    /**
     * @inheritDoc
     */
    public function get_error_handler(): ErrorHandler {
        // TODO: Implement get_error_handler() method.
    }

    /**
     * @inheritDoc
     */
    public function validate(): bool {
        // TODO: Implement validate() method.
    }

    /**
     * @inheritDoc
     */
    public function import(): ?int {
        // TODO: Implement import() method.
    }
}