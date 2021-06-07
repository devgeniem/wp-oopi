<?php
/**
 * TODO!
 */

namespace Geniem\Oopi\Attribute;


use Geniem\Oopi\Attribute\Saver\PostMetaSaver;
use Geniem\Oopi\Attribute\Saver\PostThumbnailSaver;
use Geniem\Oopi\Exception\AttributeException;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\AttachmentImportable;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;

/**
 * Class PostThumbnail
 *
 * @package Geniem\Oopi\Attribute
 */
class PostThumbnail implements Attribute {

    /**
     * The post importable.
     *
     * @var PostImportable
     */
    protected $importable;

    /**
     * The attachment importable.
     *
     * @var AttachmentImportable
     */
    protected $attachment;

    /**
     * The attribute saver.
     *
     * @var AttributeSaver|null
     */
    protected ?AttributeSaver $saver;

    /**
     * Setter for the parent object.
     *
     * @param Importable $importable The parent importable.
     *
     * @return self Return self for operation chaining.
     */
    public function set_importable( Importable $importable ) : Attribute {
        $this->importable = $importable;

        return $this;
    }

    /**
     * Get the importable.
     *
     * @return PostImportable
     */
    public function get_importable(): PostImportable {
        return $this->importable;
    }

    /**
     * Get the attachment.
     *
     * @return AttachmentImportable
     */
    public function get_attachment(): AttachmentImportable {
        return $this->attachment;
    }

    /**
     * Set the attachment.
     *
     * @param AttachmentImportable $attachment The attachment.
     *
     * @return PostThumbnail Return self to enable chaining.
     */
    public function set_attachment( AttachmentImportable $attachment ): PostThumbnail {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * PostThumbnail constructor.
     * A post thumbnail always relates to an importable object (the post)
     * and to an importable attachment.
     *
     * @param PostImportable       $importable The post object the thumbnail is for.
     * @param AttachmentImportable $attachment The attachment to be set as the attachment.
     * @param AttributeSaver|null  $saver      An optional saver. The PostThumbnailSaver by default.
     */
    public function __construct(
        PostImportable $importable,
        AttachmentImportable $attachment,
        ?AttributeSaver $saver = null
    ) {
        // Set default handlers if not passed.
        $this->saver = $saver ?? new PostThumbnailSaver();

        $this->importable = $importable;
        $this->attachment = $attachment;
    }

    /**
     * Save the thumbnail with the attached saver.
     *
     * @return int|string|null
     * @throws AttributeException Thrown if saving fails.
     */
    public function save() {
        if ( ! empty( $this->saver ) ) {
            return $this->saver->save( $this->importable, $this );
        }

        return null;
    }
}
