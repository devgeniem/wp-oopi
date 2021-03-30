<?php
/**
 * The default import handler for post objects.
 */

namespace Geniem\Oopi\Importer;

use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Post;

/**
 * Class PostImportHandler
 *
 * @package Geniem\Oopi\Handler
 */
class PostImporter extends BaseImporter {

    /**
     * Identify an importable with the OOPI id.
     *
     * @return void
     */
    public function identify() {
        // TODO: Implement identify() method.
    }

    /**
     * Import the post into WordPress.
     *
     * @return int|null The WP post id on success, null on failure.
     */
    public function import() : ?int {

        return 0;
    }
}
