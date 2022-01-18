<?php
/**
 * The attachment importable.
 */

namespace Geniem\Oopi\Importable;

use Geniem\Oopi\Attribute\AcfField;
use Geniem\Oopi\Factory\Attribute\AcfFieldFactory;
use Geniem\Oopi\Importer\AttachmentImporter;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Storage;
use Geniem\Oopi\Traits\HasPost;
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
     * Holds the attachment's parent's WP id.
     *
     * @var int|null
     */
    protected $parent_wp_id = null;

    /**
     * Holds the attachment's parent's OOPI id.
     *
     * @var string
     */
    protected $parent_oopi_id = '';

    /**
     * Defines whether this file should be treated as the post thumbnail or not.
     *
     * @var bool
     */
    protected $is_thumbnail = false;

    /**
     * Is this the thumbnail for the importable?
     *
     * @return bool
     */
    public function is_thumbnail(): bool {
        return $this->is_thumbnail;
    }

    /**
     * Set true to mark this attachment as the post thumbnail.
     *
     * @param bool $is_thumbnail The is_thumbnail.
     *
     * @return AttachmentImportable Return self to enable chaining.
     */
    public function set_is_thumbnail( ?bool $is_thumbnail ): AttachmentImportable {
        $this->is_thumbnail = $is_thumbnail;

        return $this;
    }

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

        $wp_id = Storage::get_post_id_by_oopi_id( $oopi_id );
        if ( $wp_id ) {
            // Fetch and set the existing post object.
            $this->set_wp_id( $wp_id );
        }
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

    /**
     * Getter for the post name in the set post data.
     *
     * @return string
     */
    public function get_post_name() {
        return $this->post->post_name ?? '';
    }

    /**
     * Get the attachment's parents' WP id.
     * Returns 0 if the attachment is not attached to any parent.
     *
     * @return int
     */
    public function get_parent_wp_id(): int {
        if ( $this->parent_wp_id !== null ) {
            return $this->parent_wp_id;
        }
        if ( $this->parent_oopi_id ) {
            return Storage::get_post_id_by_oopi_id( $this->parent_oopi_id ) ?: 0;
        }
        return 0;
    }

    /**
     * Get the acf.
     *
     * @return AcfField[]
     */
    public function get_acf() : array {
        return $this->acf ?? [];
    }

    /**
     * Set the parent's WP id for the attachment.
     *
     * @param int|null $parent_wp_id The parent WP id.
     *
     * @return AttachmentImportable Return self to enable chaining.
     */
    public function set_parent_wp_id( int $parent_wp_id ) : AttachmentImportable {
        $this->parent_wp_id = $parent_wp_id;

        return $this;
    }

    /**
     * Set the parent's OOPI id for the attachment.
     *
     * @param string $parent_oopi_id The parent's OOPI id.
     *
     * @return AttachmentImportable Return self to enable chaining.
     */
    public function set_parent_oopi_id( string $parent_oopi_id ): AttachmentImportable {
        $this->parent_oopi_id = $parent_oopi_id;

        return $this;
    }

    /**
     * Sets the post ACF data.
     *
     * @param array $acf_data The ACF data in an associative array.
     */
    public function set_acf( array $acf_data = [] ) {
        // Cast to AcfField objects.
        $this->acf = array_filter( array_map( function( $field ) {
            if ( $field instanceof AcfField ) {
                return $field;
            }

            try {
                return AcfFieldFactory::create( $this, $field );
            }
            catch ( \Exception $e ) {
                $this->error_handler->set_error(
                    'Unable to create the post meta attribute. Error: ' . $e->getMessage(),
                    $field
                );
            }
            return null;
        }, $acf_data ) );
    }
}
