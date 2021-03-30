<?php
/**
 * The default import handler for term objects.
 */

namespace Geniem\Oopi\Importer;

use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Traits\ImporterAccessing;

/**
 * Class TermImporter
 *
 * @package Geniem\Oopi\Importer
 */
class TermImporter extends BaseImporter {

    /**
     * Adds term meta rows for matching a WP term with an external source.
     */
    public function identify() {
        $id_prefix = Settings::get( 'id_prefix' );

        // Remove the trailing '_'.
        $identificator = rtrim( $id_prefix, '_' );

        // Set the queryable identificator.
        // Example: meta_key = 'oopi_id', meta_value = 12345
        add_term_meta( $this->term->term_id, $identificator, $this->oopi_id, true );

        // Set the indexed indentificator.
        // Example: meta_key = 'oopi_id_12345', meta_value = 12345
        add_term_meta( $this->term->term_id, $id_prefix . $this->oopi_id, $this->oopi_id, true );
    }

    public function import(): ?int {
        // TODO: Implement import() method.
    }
}
