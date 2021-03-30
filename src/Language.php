<?php
/**
 * A class for defining an object's language
 * and the related master translation.
 */

namespace Geniem\Oopi;

use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Traits\PropertyBinding;

/**
 * Class Language
 *
 * @package Geniem\Oopi
 */
class Language {

    /**
     * Add the set_data() binding method.
     */
    use PropertyBinding;

    /**
     * The importable object.
     *
     * @var Importable
     */
    protected $importable;

    /**
     * The locale.
     *
     * @var string
     */
    protected $locale;

    /**
     * The master id tells Oopi which
     * object is the master (default) translation.
     *
     * @var string
     */
    protected $master_oopi_id;

    /**
     * Language constructor.
     *
     * @param string $locale         The locale.
     * @param string $master_oopi_id The master object's Oopi id.
     */
    public function __construct( ?string $locale = '', ?string $master_oopi_id = '' ) {
        $this->locale         = $locale;
        $this->master_oopi_id = $master_oopi_id;
    }

    /**
     * Get the locale.
     *
     * @return string
     */
    public function get_locale() : string {
        return $this->locale;
    }

    /**
     * Set the locale.
     *
     * @param string $locale The locale.
     *
     * @return Language Return self to enable chaining.
     */
    public function set_locale( ?string $locale ) : Language {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get the master_oopi_id.
     *
     * @return string
     */
    public function get_master_oopi_id() : string {
        return $this->master_oopi_id;
    }

    /**
     * Set the master_oopi_id.
     *
     * @param string $master_oopi_id The master_oopi_id.
     *
     * @return Language Return self to enable chaining.
     */
    public function set_master_oopi_id( ?string $master_oopi_id ) : Language {
        $this->master_oopi_id = $master_oopi_id;

        return $this;
    }

}
