<?php

namespace Geniem\Oopi\Traits;

trait ImportStatus {

    /**
     * Defines if the importable is already improted.
     *
     * @var bool
     */
    protected bool $imported = false;

    /**
     * Set the imported status.
     *
     * @param bool $imported The import status.
     *
     * @return ImportStatus Return self to enable chaining.
     */
    public function set_imported( bool $imported ) : ImportStatus {
        $this->imported = $imported;

        return $this;
    }

    /**
     * Get the imported.
     *
     * @return bool
     */
    public function is_imported() : bool {
        return $this->imported;
    }
}
