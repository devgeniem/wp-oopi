<?php
/**
 * The Term class is used to import terms into WordPress.
 */

namespace Geniem\Oopi;

use Geniem\Oopi\Traits\PropertyBinder;
use WP_Term;

/**
 * Class Term
 *
 * Handles term importing.
 *
 * @package Geniem\Oopi
 */
class Term {

    /**
     * Add the set_data() binding method.
     */
    use PropertyBinder;

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
     * Get the term.
     *
     * @return WP_Term
     */
    public function get_term() : ?WP_Term {
        if ( empty( $this->term ) ) {
            $term_id    = Storage::get_term_id_by_oopi_id( $this->get_oopi_id() );
            $this->term = get_term( $term_id );
        }

        // If no term is found with the Oopi id, try to find by slug.
        if ( ! $this->term instanceof WP_Term ) {
            // Fetch the WP term object.
            $this->term = Storage::get_term_by_slug( $this->get_slug(), $this->get_taxonomy() );
        }

        return $this->term;
    }

    /**
     * Set the term.
     *
     * @param WP_Term $term A WP term object.
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
    public function get_language() : Language {
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
}
