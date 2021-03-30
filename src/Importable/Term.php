<?php
/**
 * The Term class is used to import terms into WordPress.
 */

namespace Geniem\Oopi\Importable;

use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Language;
use Geniem\Oopi\Storage;
use Geniem\Oopi\Traits\ImporterAccessing;
use Geniem\Oopi\Traits\PropertyBinding;
use WP_Term;

/**
 * Class Term
 *
 * Handles term importing.
 *
 * @package Geniem\Oopi
 */
class Term implements Importable {

    /**
     * Add the set_data() binding method.
     */
    use PropertyBinding;

    /**
     * Add the importer accessors.
     */
    use ImporterAccessing;

    /**
     * A unique id for external identification.
     *
     * @var string
     */
    protected $oopi_id;

    /**
     * If this is an existing term, the term is loaded here.
     *
     * @var WP_Term
     */
    protected $term;

    /**
     * The term slug.
     *
     * @var string
     */
    protected $slug;

    /**
     * The display name.
     *
     * @var string
     */
    protected $name;

    /**
     * The WP taxonomy slug.
     *
     * @var string
     */
    protected $taxonomy;

    /**
     * The term description
     *
     * @var string
     */
    protected $description = '';

    /**
     * The optional language data
     *
     * @var Language
     */
    protected $language;

    /**
     * An Oopi id of the parent term.
     *
     * @var string
     */
    protected $parent;

    /**
     * Term constructor.
     *
     * @param string       $oopi_id A term must always contain an external id.
     * @param WP_Term|null $term If a WP Term is set, the imported data
     *                           will override data in the term.
     */
    public function __construct( $oopi_id, ?WP_Term $term = null ) {
        $this->oopi_id = $oopi_id;
        $this->term    = $term;
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
     * Get the WP term id.
     *
     * @return int
     */
    public function get_term_id() : int {
        return $this->term->term_id ?? 0;
    }

    /**
     * Get the term id.
     *
     * @return int
     */
    public function get_wp_id(): ?int {
        return $this->get_term_id();
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
     * @return Term Return self to enable chaining.
     */
    public function set_term( ?WP_Term $term ) : Term {
        $this->term = $term;

        // Set properties with WP term data.
        $this->set_data( $term );

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
     * @return Term Return self to enable chaining.
     */
    public function set_slug( ?string $slug ) : Term {

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
     * @return Term Return self to enable chaining.
     */
    public function set_name( ?string $name ) : Term {

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
     * @return Term Return self to enable chaining.
     */
    public function set_taxonomy( ?string $taxonomy ) : Term {

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
     * @param string $description The description.
     *
     * @return Term Return self to enable chaining.
     */
    public function set_description( ?string $description ): Term {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the language.
     *
     * @return Language
     */
    public function get_language() : ?Language {
        return $this->language;
    }

    /**
     * Set the language.
     *
     * @param Language $language The language.
     *
     * @return Term Return self to enable chaining.
     */
    public function set_language( ?Language $language ) : Term {
        $this->language = $language;

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
     * @return Term Return self to enable chaining.
     */
    public function set_parent( ?string $parent ) : Term {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Validate the term object data.
     *
     * @param ErrorHandler $error_hander The error handler to store all validation errors.
     *
     * @return bool
     */
    public function validate( ErrorHandler $error_hander ) : bool {
        // TODO: Implement validate() method.
        return true;
    }
}
