<?php
/**
 * The attachment importable.
 */

namespace Geniem\Oopi\Importable;

use Geniem\Oopi\Importer\AttachmentImporter;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Traits\HasPostMeta;
use Geniem\Oopi\Traits\ImportableBase;
use Geniem\Oopi\Traits\Translatable;

/**
 * Class AttachmentImportable
 *
 * @package Geniem\Oopi\Importable
 */
class AttachmentImportable implements Importable {

    /**
     * The error scope.
     */
    const ESCOPE = 'attachment';

    /**
     * Use basic functionalities.
     */
    use ImportableBase;

    /**
     * Attachments can have post meta.
     */
    use HasPostMeta;

    /**
     * Use the language attribute.
     */
    use Translatable;

    /**
     * The source URL/path from which to upload the attachment file into WordPress.
     *
     * @var string
     */
    protected string $src = '';

    /**
     * The file title text.
     *
     * @var string
     */
    protected string $title = '';

    /**
     * The alt text for the attachment.
     *
     * @var string
     */
    protected string $alt = '';

    /**
     * The file caption text.
     *
     * @var string
     */
    protected string $caption = '';

    /**
     * The file description text.
     *
     * @var string
     */
    protected string $description = '';

    /**
     * Post constructor.
     *
     * @param string            $oopi_id       A unique id for the importable.
     * @param Importer|null     $importer      The importer.
     * @param ErrorHandler|null $error_handler An optional error handler.
     */
    public function __construct(
        string $oopi_id,
        ?Importer $importer = null,
        ?ErrorHandler $error_handler = null
    ) {
        $this->oopi_id       = $oopi_id;
        $this->error_handler = $error_handler ?? new OopiErrorHandler( static::ESCOPE );
        $this->importer      = $importer ?? new AttachmentImporter();
    }

    /**
     * Set the src.
     *
     * @param string $src The src.
     *
     * @return AttachmentImportable Return self to enable chaining.
     */
    public function set_src( string $src ) : AttachmentImportable {
        $this->src = $src;

        return $this;
    }

    /**
     * Set the title.
     *
     * @param string $title The title.
     *
     * @return AttachmentImportable Return self to enable chaining.
     */
    public function set_title( string $title ) : AttachmentImportable {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the alt.
     *
     * @param string $alt The alt.
     *
     * @return AttachmentImportable Return self to enable chaining.
     */
    public function set_alt( string $alt ) : AttachmentImportable {
        $this->alt = $alt;

        return $this;
    }

    /**
     * Set the caption.
     *
     * @param string $caption The caption.
     *
     * @return AttachmentImportable Return self to enable chaining.
     */
    public function set_caption( string $caption ) : AttachmentImportable {
        $this->caption = $caption;

        return $this;
    }

    /**
     * Set the description.
     *
     * @param string $description The description.
     *
     * @return AttachmentImportable Return self to enable chaining.
     */
    public function set_description( string $description ) : AttachmentImportable {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the src.
     *
     * @return string
     */
    public function get_src(): string {
        return $this->src;
    }

    /**
     * Get the alt.
     *
     * @return string
     */
    public function get_alt(): string {
        return $this->alt;
    }

    /**
     * Get the caption.
     *
     * @return string
     */
    public function get_caption(): string {
        return $this->caption;
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Get the title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }
}
