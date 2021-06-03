<?php
/**
 * The Term class is used to import terms into WordPress.
 */

namespace Geniem\Oopi\Importable;

use Geniem\Oopi\Attribute\TermMeta;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Factory\Attribute\TermMetaFactory;
use Geniem\Oopi\Importer\TermImporter;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Storage;
use Geniem\Oopi\Traits\ImportableBase;
use Geniem\Oopi\Traits\Translatable;
use WP_Term;

/**
 * Class TermImportable
 *
 * Handles term importing.
 *
 * @package Geniem\Oopi
 */
class TermImportable implements Importable {

    /**
     * The error scope.
     */
    const ESCOPE = 'term';

    /**
     * Use basic functionalities.
     */
    use ImportableBase;

    /**
     * Use the language attribute.
     */
    use Translatable;

    /**
     * If this is an existing term, the term is loaded here.
     *
     * @var WP_Term|null
     */
    protected ?WP_Term $term = null;

    /**
     * The term slug.
     *
     * @var string
     */
    protected string $slug;

    /**
     * The display name.
     *
     * @var string
     */
    protected string $name;

    /**
     * The WP taxonomy slug.
     *
     * @var string
     */
    protected string $taxonomy;

    /**
     * The term description
     *
     * @var string|null
     */
    protected ?string $description = '';

    /**
     * An Oopi id of the parent term.
     *
     * @var string|null
     */
    protected ?string $parent = null;

    /**
     * Metadata in an associative array.
     *
     * @var TermMeta[]
     */
    protected array $meta = [];

    /**
     * Term constructor.
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
        $this->importer      = $importer ?? new TermImporter();

        $term_id = Storage::get_term_id_by_oopi_id( $this->oopi_id );

        if ( $term_id ) {
            $term = get_term( $term_id );
            if ( $term instanceof WP_Term ) {
                $this->set_term( get_term( $term_id ) );
            }
        }
    }

    /**
     * Get the oopi id.
     *
     * @return string
     */
    public function get_oopi_id() : string {
        return $this->oopi_id;
    }

    /**
     * Get the term id.
     *
     * @return int
     */
    public function get_wp_id(): ?int {
        return $this->term->term_id ?? 0;
    }

    /**
     * Getter for the term slug in the set term data.
     *
     * @return string
     */
    public function get_term_slug() {
        return $this->term->slug ?? '';
    }

    /**
     * Get the term.
     *
     * @return WP_Term
     */
    public function get_term() : ?WP_Term {
        if ( empty( $this->term ) ) {
            $term_id    = Storage::get_term_id_by_oopi_id( $this->get_oopi_id() );
            $term_by_id = get_term( $term_id );
            $this->term = $term_by_id instanceof WP_Term ? $term_by_id : null;
        }

        // If no term is found with the Oopi id, try to find by slug.
        if ( ! $this->term instanceof WP_Term ) {
            // Fetch the WP term object.
            $term_by_slug = Storage::get_term_by_slug( $this->get_slug(), $this->get_taxonomy() );
            if ( $term_by_slug instanceof WP_Term ) {
                $existing_oopi_id = get_term_meta( $term_by_slug->term_id, Storage::get_idenfiticator(), true );
                // Set term if it does not belong to another Oopi term.
                if ( empty( $existing_oopi_id ) ) {
                    $this->term = $term_by_slug;
                }
            }
        }

        return $this->term;
    }

    /**
     * Set the term.
     *
     * @param WP_Term|null $term A WP term object.
     *
     * @return TermImportable Return self to enable chaining.
     */
    public function set_term( ?WP_Term $term ) : TermImportable {
        // Hold onto the current id.
        $current_id = $this->get_wp_id();

        $this->term = $term;

        // Set the WP id if found.
        if ( ! $current_id && $term->term_id ) {
            $this->set_wp_id( $term->term_id );
        }

        // Ensure the id is not changed.
        if ( $current_id ) {
            $this->term->term_id = $current_id;
        }

        // Set properties with WP term data.
        $this->set_name( $term->name );
        $this->set_slug( $term->slug );
        $this->set_description( $term->description );
        $this->set_taxonomy( $term->taxonomy );

        return $this;
    }

    /**
     * Get the slug.
     *
     * @return string
     */
    public function get_slug() : string {

        return $this->slug;
    }

    /**
     * Set the slug.
     *
     * @param string $slug The slug.
     *
     * @return TermImportable Return self to enable chaining.
     */
    public function set_slug( string $slug ) : TermImportable {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get the display name.
     *
     * @return string
     */
    public function get_name() : string {

        return $this->name;
    }

    /**
     * Set the display name.
     *
     * @param string $name The name.
     *
     * @return TermImportable Return self to enable chaining.
     */
    public function set_name( string $name ) : TermImportable {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the taxonomy.
     *
     * @return string
     */
    public function get_taxonomy() : string {

        return $this->taxonomy;
    }

    /**
     * Set the taxonomy slug.
     *
     * @param string $taxonomy The WP taxonomy slug.
     *
     * @return TermImportable Return self to enable chaining.
     */
    public function set_taxonomy( string $taxonomy ) : TermImportable {
        $this->taxonomy = $taxonomy;

        return $this;
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
     * Set the description.
     *
     * @param string|null $description The description.
     *
     * @return TermImportable Return self to enable chaining.
     */
    public function set_description( ?string $description = '' ): TermImportable {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the parent term Oopi id.
     *
     * @return string|null
     */
    public function get_parent() : ?string {
        return $this->parent;
    }

    /**
     * Set the parent term Oopi id.
     *
     * @param string|null $parent The parent id.
     *
     * @return TermImportable Return self to enable chaining.
     */
    public function set_parent( ?string $parent = null ) : TermImportable {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Sets the term meta data.
     *
     * @param array $meta_data The meta data.
     */
    public function set_meta( array $meta_data = [] ) {

        // Cast data to term meta.
        $this->meta = array_filter( array_map( function( $meta ) {
            if ( $meta instanceof TermMeta ) {
                return $meta;
            }

            try {
                return TermMetaFactory::create( $this, $meta );
            }
            catch ( \Exception $e ) {
                $this->error_handler->set_error(
                    'Unable to create the term meta attribute. Error: ' . $e->getMessage(),
                    $e
                );
            }
            return null;
        }, $meta_data ) );
    }

    /**
     * Import the term using the attached importer.
     *
     * @return int|null The imported WP term id.
     * @throws TypeException Thrown if the importable is of the wrong type.
     */
    public function import(): ?int {
        if ( ! empty( $this->importer ) ) {
            return $this->importer->import( $this, $this->error_handler );
        }

        return null;
    }
}
